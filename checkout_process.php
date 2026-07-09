<?php
session_start();

require_once 'config/database.php';
require_once 'config/stripe.php';

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
    $lineItems = [];

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

        $price = (float) $item['price'];
        $quantity = (int) $item['quantity'];

        $total += $price * $quantity;

        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $item['name'] . ' - Taille ' . $item['size'],
                ],
                'unit_amount' => (int) round($price * 100),
            ],
            'quantity' => $quantity,
        ];
    }

    $orderQuery = $pdo->prepare("
        INSERT INTO orders (customer_id, total, status, payment_status)
        VALUES (?, ?, 'pending', 'unpaid')
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

    $domain = 'https://belowDreams.com';

    $checkoutSession = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'client_reference_id' => $orderId,
        'line_items' => $lineItems,
        'success_url' => $domain . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $domain . '/checkout.php?payment=cancelled',
        'metadata' => [
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ],
    ]);

    $updateStripeSession = $pdo->prepare("
        UPDATE orders
        SET stripe_session_id = ?
        WHERE id = ?
    ");

    $updateStripeSession->execute([
        $checkoutSession->id,
        $orderId
    ]);

    $pdo->commit();

    header('Location: ' . $checkoutSession->url);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['checkout_error'] = "Une erreur est survenue lors de la création du paiement.";

    header('Location: checkout.php');
    exit;
}