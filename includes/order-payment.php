<?php

declare(strict_types=1);

/**
 * Confirme une commande après validation du paiement Stripe.
 *
 * Préconditions :
 * - Transaction SQL déjà ouverte.
 * - Commande déjà verrouillée avec FOR UPDATE.
 * - Session Stripe vérifiée et effectivement payée.
 *
 * Retour :
 * true  = nouvelle confirmation
 * false = commande déjà confirmée
 */
function confirmOrderPayment(
    PDO $pdo,
    int $orderId,
    array $lockedOrder,
    string $stripeSessionId,
    ?string $paymentIntentId
): bool {
    if (!$pdo->inTransaction()) {
        throw new LogicException(
            'La confirmation nécessite une transaction SQL.'
        );
    }

    if ((int) ($lockedOrder['id'] ?? 0) !== $orderId) {
        throw new LogicException(
            'La commande verrouillée ne correspond pas.'
        );
    }

    $storedSessionId = $lockedOrder['stripe_session_id']
        ?? null;

    if (
        !is_string($storedSessionId)
        || $storedSessionId === ''
        || !hash_equals(
            $storedSessionId,
            $stripeSessionId
        )
    ) {
        throw new RuntimeException(
            'Session Stripe différente de celle de la commande.'
        );
    }

    $reservationStatus = $lockedOrder[
        'stock_reservation_status'
    ] ?? null;

    $paymentStatus = $lockedOrder[
        'payment_status'
    ] ?? null;

    if (
        $reservationStatus === 'confirmed'
        && $paymentStatus === 'paid'
    ) {
        return false;
    }

    if (
        $reservationStatus !== 'reserved'
        || $paymentStatus !== 'unpaid'
    ) {
        throw new RuntimeException(
            'Transition de paiement non autorisée.'
        );
    }

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
          AND stripe_session_id = ?
          AND stock_reservation_status = 'reserved'
          AND payment_status = 'unpaid'
    ");

    $updateOrder->execute([
        $paymentIntentId,
        $orderId,
        $stripeSessionId
    ]);

    if ($updateOrder->rowCount() !== 1) {
        throw new RuntimeException(
            'Impossible de confirmer la commande.'
        );
    }

    return true;
}
