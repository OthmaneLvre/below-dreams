<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

require_once 'partials/header.php';
require_once 'partials/sidebar.php';

// Sécurité : accès admin uniquement
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifie si un ID est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = (int) $_GET['id'];

// Suppression du produit
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$productId]);

header('Location: dashboard.php');
exit;
