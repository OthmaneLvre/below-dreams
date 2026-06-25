<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $sizes = trim($_POST['sizes']);
    $image = trim($_POST['image']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if ($name && $slug && $category && $price) {
        $query = $pdo->prepare("
            INSERT INTO products (
                name, slug, category, price, description,
                status, sizes, image, is_featured
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $query->execute([
            $name,
            $slug,
            $category,
            $price,
            $description,
            $status,
            $sizes,
            $image,
            $isFeatured
        ]);

        header('Location: products.php');
        exit;
    }

    $error = "Merci de remplir les champs obligatoires.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un produit | Below Dreams</title>
</head>
<body>

<h1>Ajouter un produit</h1>

<p>
    <a href="products.php">Retour aux produits</a>
</p>

<?php if (!empty($error)) : ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">

    <label>Nom du produit *</label><br>
    <input type="text" name="name" required><br><br>

    <label>Slug *</label><br>
    <input type="text" name="slug" placeholder="ex: tshirt-oversize-unisexe" required><br><br>

    <label>Catégorie *</label><br>
    <select name="category" required>
        <option value="">Choisir</option>
        <option value="pantalon">Pantalon</option>
        <option value="tshirt">T-shirt</option>
        <option value="hoodie">Hoodie</option>
        <option value="short">Short</option>
    </select><br><br>

    <label>Prix *</label><br>
    <input type="number" name="price" step="0.01" required><br><br>

    <label>Description</label><br>
    <textarea name="description" rows="5"></textarea><br><br>

    <label>Statut</label><br>
    <select name="status">
        <option value="preorder">Précommande</option>
        <option value="stock">Stock</option>
    </select><br><br>

    <label>Tailles disponibles</label><br>
    <input type="text" name="sizes" placeholder="ex: S,M,L,XL"><br><br>

    <label>Image</label><br>
    <input type="text" name="image" placeholder="ex: assets/images/product/mon-image.jpg"><br><br>

    <label>
        <input type="checkbox" name="is_featured">
        Mettre en avant
    </label><br><br>

    <button type="submit">
        Ajouter le produit
    </button>

</form>

</body>
</html>
