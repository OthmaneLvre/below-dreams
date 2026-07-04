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
    $stock = (int) $_POST['stock'];
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $sizes = trim($_POST['sizes']);
    $image = '';
    $productImages = [];

    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../assets/images/product/';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($_FILES['images']['name'] as $index => $originalName) {
            if (empty($originalName)) {
                continue;
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $error = "Format d'image non autorisé.";
                break;
            }

            $safeName = uniqid('product-', true) . '.' . $extension;
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$index], $targetPath)) {
                $imagePath = 'assets/images/product/' . $safeName;

                $productImages[] = $imagePath;

                if ($index === 0) {
                    $image = $imagePath;
                }
            }
        }
    }

    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if ($name && $slug && $category && $price) {
        $query = $pdo->prepare("
            INSERT INTO products (
                name, slug, category, price, stock, description,
                status, sizes, image, is_featured
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $query->execute([
            $name,
            $slug,
            $category,
            $price,
            $stock,
            $description,
            $status,
            $sizes,
            $image,
            $isFeatured
        ]);

        $productId = $pdo->lastInsertId();

        if (!empty($productImages)) {
            $imageQuery = $pdo->prepare("
                INSERT INTO product_images (
                    product_id,
                    image_path,
                    is_main,
                    sort_order
                )
                VALUES (?, ?, ?, ?)
            ");

            foreach ($productImages as $index => $imagePath) {
                $imageQuery->execute([
                    $productId,
                    $imagePath,
                    $index === 0 ? 1 : 0,
                    $index
                ]);
            }
        }

        header('Location: products.php');
        exit;
    }

    $error = "Merci de remplir les champs obligatoires.";
}

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Ajouter un produit</h1>
            <p>Ajoute un nouveau produit à la boutique Below Dreams.</p>
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
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" placeholder="ex: tshirt-oversize-unisexe" required>
                </div>

                <div class="form-group">
                    <label>Catégorie *</label>
                    <select name="category" required>
                        <option value="">Choisir</option>
                        <option value="pantalon">Pantalon</option>
                        <option value="tshirt">T-shirt</option>
                        <option value="hoodie">Hoodie</option>
                        <option value="short">Short</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prix *</label>
                    <input type="number" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" min="0" value="0" required>
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <select name="status">
                        <option value="preorder">Précommande</option>
                        <option value="stock">Stock</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tailles disponibles</label>
                    <input type="text" name="sizes" placeholder="ex: S,M,L,XL">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="6"></textarea>
            </div>

            <div class="form-group">
                <label>Image du produit</label>
                <input type="file" name="images[]" accept="image/*" multiple>
                <small>La première image sera utilisée comme image principale.</small>
            </div>

            <label class="checkbox-group">
                <input type="checkbox" name="is_featured">
                <span>Mettre en avant</span>
            </label>

            <div class="form-actions">
                <button type="submit" class="admin-btn">
                    Ajouter le produit
                </button>
            </div>

        </form>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>
