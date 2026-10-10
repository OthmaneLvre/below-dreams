<?php
declare(strict_types=1);

/**
 * Libère le stock réservé pour une commande.
 *
 * IMPORTANT :
 * - La transaction doit déjà être ouverte.
 * - La commande doit déjà être verrouillée avec FOR UPDATE.
 * - Cette fonction ne valide ni n'annule la transaction.
 */

function releaseOrderStockReservation(
    PDO $pdo,
    int $orderId,
    array $lockedOrder
): bool {
    if (!$pdo->inTransaction()) {
        throw new LogicException(
            'La libération du stock nécessite une transaction.'
        );
    }

    if ((int) ($lockedOrder['id'] ?? 0) !== $orderId) {
        throw new LogicException(
            'La commande verrouillée ne correspond pas.'
        );
    }

    $reservationStatus = $lockedOrder[
        'stock_reservation_status'
    ] ?? null;

    $paymentStatus = $lockedOrder[
        'payment_status'
    ] ?? null;

    if ($reservationStatus === 'released') {
        return false;
    }

    if (
        $reservationStatus === 'confirmed'
        || $paymentStatus === 'paid'
    ) {
        throw new LogicException(
            'Impossible de libérer une commande payée.'
        );
    }

    if ($reservationStatus !== 'reserved') {
        throw new LogicException(
            'La commande ne possède pas de réservation active.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération des quantités par produit
    |--------------------------------------------------------------------------
    */

    $itemsQuery = $pdo->prepare("
        SELECT
            product_id,
            SUM(quantity) AS total_quantity
        FROM order_items
        WHERE order_id = ?
        GROUP BY product_id
    ");

    $itemsQuery->execute([$orderId]);

    $items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new RuntimeException(
            'Impossible de libérer une réservation sans articles.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restitution des stocks
    |--------------------------------------------------------------------------
    */

    $restoreStockQuery = $pdo->prepare("
        UPDATE products
        SET stock = stock + ?
        WHERE id = ?
    ");

    foreach ($items as $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['total_quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            throw new RuntimeException(
                'Article invalide dans la réservation.'
            );
        }

        $restoreStockQuery->execute([
            $quantity,
            $productId
        ]);

        if ($restoreStockQuery->rowCount() !== 1) {
            throw new RuntimeException(
                'Impossible de restituer le stock du produit '
                . $productId
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Transition reserved -> released
    |--------------------------------------------------------------------------
    */

    $updateOrder = $pdo->prepare("
        UPDATE orders
        SET
            status = 'cancelled',
            payment_status = 'unpaid',
            stock_reservation_status = 'released',
            updated_at = NOW()
        WHERE id = ?
          AND stock_reservation_status = 'reserved'
          AND payment_status <> 'paid'
    ");

    $updateOrder->execute([$orderId]);

    if ($updateOrder->rowCount() !== 1) {
        throw new RuntimeException(
            'Impossible de finaliser la libération du stock.'
        );
    }

    return true;
}
