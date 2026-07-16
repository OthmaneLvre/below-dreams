<?php

require_once 'config/database.php';
require_once 'config/stripe.php';
require_once 'includes/mailer.php';

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sigHeader,
        $stripeWebhookSecret
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit('Invalid payload');
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

/*
|--------------------------------------------------------------------------
| Paiement validé
|--------------------------------------------------------------------------
*/

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $orderId = $session->metadata->order_id ?? null;
    $paymentIntentId = $session->payment_intent ?? null;
    $paymentStatus = $session->payment_status ?? null;

    if ($orderId && $paymentStatus === 'paid') {
        try {
            $pdo->beginTransaction();

            $checkOrder = $pdo->prepare("
                SELECT
                    payment_status,
                    confirmation_email_sent_at
                FROM orders
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $checkOrder->execute([(int) $orderId]);
            $currentOrder = $checkOrder->fetch(PDO::FETCH_ASSOC);

            if (!$currentOrder) {
                $pdo->rollBack();

                http_response_code(200);
                exit('Order not found');
            }

            $alreadyPaid = $currentOrder['payment_status'] === 'paid';

            $emailAlreadySent = !empty(
                $currentOrder['confirmation_email_sent_at']
            );

            if (!$alreadyPaid) {
                $updateOrder = $pdo->prepare("
                    UPDATE orders
                    SET
                        status = 'paid',
                        payment_status = 'paid',
                        stripe_payment_intent_id = ?,
                        paid_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $updateOrder->execute([
                    $paymentIntentId,
                    (int) $orderId
                ]);

                $itemsQuery = $pdo->prepare("
                    SELECT
                        order_items.product_id,
                        order_items.quantity,
                        products.status
                    FROM order_items
                    INNER JOIN products
                        ON order_items.product_id = products.id
                    WHERE order_items.order_id = ?
                ");

                $itemsQuery->execute([(int) $orderId]);
                $orderItems = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

                $stockQuery = $pdo->prepare("
                    UPDATE products
                    SET stock = GREATEST(stock - ?, 0)
                    WHERE id = ?
                    AND status = 'stock'
                ");

                foreach ($orderItems as $item) {
                    if ($item['status'] === 'stock') {
                        $stockQuery->execute([
                            (int) $item['quantity'],
                            (int) $item['product_id']
                        ]);
                    }
                }
            }

            if (!$emailAlreadySent) {
                $emailSent = sendOrderConfirmationEmail(
                    $pdo,
                    (int) $orderId
                );

                if ($emailSent) {
                    $markEmailSent = $pdo->prepare("
                        UPDATE orders
                        SET confirmation_email_sent_at = NOW()
                        WHERE id = ?
                        AND confirmation_email_sent_at IS NULL
                    ");

                    $markEmailSent->execute([
                        (int) $orderId
                    ]);
                }
            }

            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            http_response_code(500);
            exit('Webhook processing error');
        }
    }
}

/*
|--------------------------------------------------------------------------
| Session Stripe expirée
|--------------------------------------------------------------------------
|
| Une commande est créée avant la redirection vers Stripe.
| Si la session expire sans paiement, elle passe en annulée.
|
*/

if ($event->type === 'checkout.session.expired') {
    $session = $event->data->object;

    $orderId = $session->metadata->order_id ?? null;

    if ($orderId) {
        try {
            $cancelOrder = $pdo->prepare("
                UPDATE orders
                SET
                    status = 'cancelled',
                    payment_status = 'unpaid',
                    updated_at = NOW()
                WHERE id = ?
                AND payment_status != 'paid'
                AND status = 'pending'
            ");

            $cancelOrder->execute([(int) $orderId]);

        } catch (Throwable $e) {
            http_response_code(500);
            exit('Webhook expiration processing error');
        }
    }
}

http_response_code(200);
echo 'Webhook received';