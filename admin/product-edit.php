<?php
session_start();
require_once '../config/database.php';

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

// Récupération du produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: dashboard.php');
    exit;
}

// Modification du produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];

    $stmt = $pdo->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, stock = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $description,
        $price,
        $stock,
        $productId
    ]);

    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un produit</title>
</head>
<body>

    <h1>Modifier le produit</h1>

    <form method="POST">
        <label>Nom du produit</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea>

        <label>Prix</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>

        <label>Stock</label>
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required>

        <button type="submit">Modifier</button>
    </form>

    <a href="dashboard.php">Retour au tableau de bord</a>

</body>
</html>
