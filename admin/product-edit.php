<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $sizes = trim($_POST['sizes']);
    $image = $product['image'];
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/product/';

        $fileName = time() . '-' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);

            $image = 'assets/images/product/' . $fileName;
        } else {
            $error = "Format d'image non autorisé.";
        }
    }

    if (empty($error) && $name && $slug && $category && $price) {
        $stmt = $pdo->prepare("
            UPDATE products
            SET name = ?,
                slug = ?,
                category = ?,
                price = ?,
                description = ?,
                status = ?,
                sizes = ?,
                image = ?,
                is_featured = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $slug,
            $category,
            $price,
            $description,
            $status,
            $sizes,
            $image,
            $isFeatured,
            $productId
        ]);

        header('Location: products.php');
        exit;
    }

    if (empty($error)) {
        $error = "Merci de remplir les champs obligatoires.";
    }
}

$pageTitle = "Modifier un produit | Below Dreams";

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Modifier un produit</h1>
            <p>Modifie les informations du produit sélectionné.</p>
        </div>

        <a href="products.php" class="admin-btn-secondary">
            Retour aux produits
        </a>
    </header>

    <section class="admin-section">

        <?php if (!empty($error)) : ?>
            <p class="admin-alert">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($product['slug']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Catégorie *</label>
                    <select name="category" required>
                        <option value="">Choisir</option>
                        <option value="pantalon" <?= $product['category'] === 'pantalon' ? 'selected' : '' ?>>Pantalon</option>
                        <option value="tshirt" <?= $product['category'] === 'tshirt' ? 'selected' : '' ?>>T-shirt</option>
                        <option value="hoodie" <?= $product['category'] === 'hoodie' ? 'selected' : '' ?>>Hoodie</option>
                        <option value="short" <?= $product['category'] === 'short' ? 'selected' : '' ?>>Short</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prix *</label>
                    <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <select name="status">
                        <option value="preorder" <?= $product['status'] === 'preorder' ? 'selected' : '' ?>>Précommande</option>
                        <option value="stock" <?= $product['status'] === 'stock' ? 'selected' : '' ?>>Stock</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tailles disponibles</label>
                    <input type="text" name="sizes" value="<?= htmlspecialchars($product['sizes']) ?>" placeholder="ex: S,M,L,XL">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Image actuelle</label>

                <?php if (!empty($product['image'])) : ?>
                    <img src="../<?= htmlspecialchars($product['image']) ?>" class="product-preview" alt="">
                <?php else : ?>
                    <span class="empty-image">Aucune image</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Remplacer l’image</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <label class="checkbox-group">
                <input type="checkbox" name="is_featured" <?= $product['is_featured'] ? 'checked' : '' ?>>
                <span>Mettre en avant</span>
            </label>

            <div class="form-actions">
                <button type="submit" class="admin-btn">
                    Enregistrer les modifications
                </button>
            </div>

        </form>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>
