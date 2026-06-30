<?php
session_start();

require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$cartJson = $_POST['cart'] ?? '';
$cart = json_decode($cartJson, true);

if (empty($cart) || !is_array($cart)) {
    header('Location: cart.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $total = 0;

    foreach ($cart as $item) {
        if (
            empty($item['product_id']) ||
            empty($item['name']) ||
            empty($item['price']) ||
            empty($item['quantity']) ||
            empty($item['size'])
        ) {
            throw new Exception('Panier invalide.');
        }

        $total += (float) $item['price'] * (int) $item['quantity'];
    }

    $orderQuery = $pdo->prepare("
        INSERT INTO orders (customer_id, total, status)
        VALUES (?, ?, 'pending')
    ");

    $orderQuery->execute([
        $_SESSION['customer_id'],
        $total
    ]);

    $orderId = $pdo->lastInsertId();

    $orderNumber = 'BD-' . date('Y') . str_pad($orderId, 5, '0', STR_PAD_LEFT);

    $updateOrderNumber = $pdo->prepare("
        UPDATE orders
        SET order_number = ?
        WHERE id = ?
    ");

    $updateOrderNumber->execute([
        $orderNumber,
        $orderId
    ]);

    $itemQuery = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            product_image,
            size,
            quantity,
            price
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $itemQuery->execute([
            $orderId,
            (int) $item['product_id'],
            $item['name'],
            $item['image'] ?? null,
            $item['size'],
            (int) $item['quantity'],
            (float) $item['price']
        ]);
    }

    $pdo->commit();

    $_SESSION['last_order_id'] = $orderId;

    header('Location: success.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();

    $_SESSION['checkout_error'] = "Une erreur est survenue lors de la création de la commande.";

    header('Location: checkout.php');
    exit;
}