<?php

require_once 'config/database.php';
require_once 'config/stripe.php';

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
    }
}

http_response_code(200);
echo 'Webhook received';