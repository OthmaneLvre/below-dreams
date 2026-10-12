
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/stock-reservation.php';
require_once __DIR__ . '/includes/order-payment.php';
require_once __DIR__ . '/includes/order-email.php';

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sigHeader,
        $stripeWebhookSecret
    );
} catch (\UnexpectedValueException $e) {
    error_log('[STRIPE WEBHOOK] Invalid payload: ' . $e->getMessage());
    http_response_code(400);
    exit('Invalid payload');
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('[STRIPE WEBHOOK] Invalid signature: ' . $e->getMessage());
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
    error_log('[STRIPE WEBHOOK] Invalid order metadata');
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
            confirmation_email_sent_at,
            total
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

        /*
        |--------------------------------------------------------------------------
        | Vérification du montant et de la devise
        |--------------------------------------------------------------------------
        */

        $expectedAmount = (int) round(
            ((float) $order['total']) * 100
        );

        $stripeAmount = $session->amount_total ?? null;
        $stripeCurrency = $session->currency ?? null;

        if (
            !is_int($stripeAmount)
            || $stripeAmount !== $expectedAmount
            || $stripeCurrency !== 'eur'
        ) {
            throw new RuntimeException(
                'Montant ou devise Stripe incompatible avec la commande.'
            );
        }

        $paymentIntentId = $session->payment_intent ?? null;

        if (
            $paymentIntentId !== null
            && !is_string($paymentIntentId)
        ) {
            throw new RuntimeException(
                'Identifiant de paiement Stripe invalide.'
            );
        }

        $newlyConfirmed = confirmOrderPayment(
            $pdo,
            $orderId,
            $order,
            $sessionId,
            $paymentIntentId
        );

        $sendConfirmation = $newlyConfirmed || empty(
            $order['confirmation_email_sent_at']
        );
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
| Envoi de l'e-mail après validation de la transaction
|--------------------------------------------------------------------------
*/

if ($sendConfirmation) {
    try {
        processOrderConfirmationEmail($pdo, $orderId);
    } catch (Throwable $e) {
        error_log(
            '[STRIPE WEBHOOK] Confirmation email failed'
            . ' | Order ' . $orderId
            . ' | ' . $e->getMessage()
        );
    }
}

http_response_code(200);
echo 'Webhook received';