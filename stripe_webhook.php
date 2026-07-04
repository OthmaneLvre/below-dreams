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

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $orderId = $session->metadata->order_id ?? null;
    $paymentIntentId = $session->payment_intent ?? null;
    $paymentStatus = $session->payment_status ?? null;

    if ($orderId && $paymentStatus === 'paid') {

        $checkOrder = $pdo->prepare("
            SELECT payment_status
            FROM orders
            WHERE id = ?
            LIMIT 1
        ");

        $checkOrder->execute([$orderId]);

        $currentOrder = $checkOrder->fetch(PDO::FETCH_ASSOC);

        if (!$currentOrder || $currentOrder['payment_status'] === 'paid') {
            http_response_code(200);
            exit('Already processed');
        }

        $query = $pdo->prepare("
            UPDATE orders
            SET
                status = 'paid',
                payment_status = 'paid',
                stripe_payment_intent_id = ?,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

        $query->execute([
            $paymentIntentId,
            $orderId
        ]);

        $itemsQuery = $pdo->prepare("
            SELECT
                order_items.product_id,
                order_items.quantity,
                products.status
            FROM order_items
            INNER JOIN products ON order_items.product_id = products.id
            WHERE order_items.order_id = ?    
        ");

        $itemsQuery->execute([$orderId]);

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
        
        sendOrderConfirmationEmail($pdo, (int) $orderId);
    }
}

http_response_code(200);
echo 'Webhook received';