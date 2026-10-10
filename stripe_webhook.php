
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/stock-reservation.php';

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

$handledEvents = [
    'checkout.session.completed',
    'checkout.session.expired'
];

if (!in_array($event->type, $handledEvents, true)) {
    http_response_code(200);
    exit('Event ignored');
}

$session = $event->data->object;

$orderId = filter_var(
    $session->metadata->order_id ?? null,
    FILTER_VALIDATE_INT
);

$sessionId = $session->id ?? null;

if (!$orderId || !is_string($sessionId) || $sessionId === '') {
    http_response_code(400);
    exit('Invalid order metadata');
}

$sendConfirmation = false;

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Verrouillage de la commande
    |--------------------------------------------------------------------------
    */

    $orderQuery = $pdo->prepare("
        SELECT
            id,
            status,
            payment_status,
            stock_reservation_status,
            stripe_session_id,
            confirmation_email_sent_at
        FROM orders
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $orderQuery->execute([$orderId]);

    $order = $orderQuery->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new RuntimeException(
            'Commande introuvable pour le webhook Stripe.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification de la session Stripe
    |--------------------------------------------------------------------------
    |
    | Si la session n'est pas encore enregistrée localement,
    | on renvoie une erreur pour permettre à Stripe de réessayer.
    |
    */

    if (empty($order['stripe_session_id'])) {
        throw new RuntimeException(
            'Session Stripe non encore associée à la commande.'
        );
    }

    if (!hash_equals(
        (string) $order['stripe_session_id'],
        $sessionId
    )) {
        throw new RuntimeException(
            'Session Stripe différente de celle de la commande.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Paiement confirmé
    |--------------------------------------------------------------------------
    */

    if ($event->type === 'checkout.session.completed') {

        if (($session->payment_status ?? null) !== 'paid') {
            $pdo->commit();

            http_response_code(200);
            exit('Payment not yet confirmed');
        }

        if ($order['stock_reservation_status'] === 'reserved') {

            $paymentIntentId = $session->payment_intent ?? null;

            $updateOrder = $pdo->prepare("
                UPDATE orders
                SET
                    status = 'paid',
                    payment_status = 'paid',
                    stock_reservation_status = 'confirmed',
                    stripe_payment_intent_id = ?,
                    paid_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
                  AND stock_reservation_status = 'reserved'
                  AND payment_status <> 'paid'
            ");

            $updateOrder->execute([
                $paymentIntentId,
                $orderId
            ]);

            if ($updateOrder->rowCount() !== 1) {
                throw new RuntimeException(
                    'Impossible de confirmer la commande.'
                );
            }

            $sendConfirmation = true;

        } elseif (
            $order['stock_reservation_status'] === 'confirmed'
            && $order['payment_status'] === 'paid'
        ) {
            // Événement déjà traité : aucune modification du stock.
            $sendConfirmation = empty(
                $order['confirmation_email_sent_at']
            );

        } else {
            throw new RuntimeException(
                'Paiement reçu pour une réservation non valide.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Session expirée
    |--------------------------------------------------------------------------
    */

    if ($event->type === 'checkout.session.expired') {

        if (
            $order['stock_reservation_status'] === 'reserved'
            && $order['payment_status'] !== 'paid'
        ) {
            releaseOrderStockReservation(
                $pdo,
                $orderId,
                $order
            );
        }
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        '[STRIPE WEBHOOK] '
        . $event->type
        . ' | Order '
        . $orderId
        . ' | '
        . $e->getMessage()
    );

    http_response_code(500);
    exit('Webhook processing error');
}

/*
|--------------------------------------------------------------------------
| Confirmation par e-mail hors transaction
|--------------------------------------------------------------------------
*/

if ($sendConfirmation) {
    try {
        $emailSent = sendOrderConfirmationEmail(
            $pdo,
            $orderId
        );

        if ($emailSent) {
            $markEmailSent = $pdo->prepare("
                UPDATE orders
                SET confirmation_email_sent_at = NOW()
                WHERE id = ?
                  AND payment_status = 'paid'
                  AND confirmation_email_sent_at IS NULL
            ");

            $markEmailSent->execute([$orderId]);
        }
    } catch (Throwable $e) {
        error_log(
            '[ORDER EMAIL] Order '
            . $orderId
            . ' | '
            . $e->getMessage()
        );
    }
}

http_response_code(200);
echo 'Webhook received';
