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
    $image = '';

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
                <label>Image</label>
                <input type="file" name="image" accept="image/*">
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
