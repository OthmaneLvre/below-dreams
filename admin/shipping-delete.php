<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$shippingMethodId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$shippingMethodId) {
    header('Location: shipping.php');
    exit;
}

$query = $pdo->prepare("
    SELECT id, logo
    FROM shipping_methods
    WHERE id = ?
    LIMIT 1
");

$query->execute([$shippingMethodId]);
$shippingMethod = $query->fetch(PDO::FETCH_ASSOC);

if (!$shippingMethod) {
    header('Location: shipping.php');
    exit;
}

try {
    $deleteQuery = $pdo->prepare("
        DELETE FROM shipping_methods
        WHERE id = ?
    ");

    $deleteQuery->execute([$shippingMethodId]);

    if (!empty($shippingMethod['logo'])) {
        $logoFile = dirname(__DIR__)
            . '/'
            . $shippingMethod['logo'];

        if (is_file($logoFile)) {
            unlink($logoFile);
        }
    }

    header('Location: shipping.php?deleted=1');
    exit;

} catch (PDOException $e) {
    header('Location: shipping.php?delete_error=1');
    exit;
}