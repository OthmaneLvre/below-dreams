<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/image-upload.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Modifier un produit | Below Dreams';

$error = '';

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1) {
    header('Location: products.php');
    exit;
}

$productQuery = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");

$productQuery->execute([$productId]);

$product = $productQuery->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $name = trim($_POST['name'] ?? '');

    $slug = sanitizeImageFilename(
        trim($_POST['slug'] ?? '')
    );

    $category = trim($_POST['category'] ?? '');

    $priceValue = str_replace(
        ',',
        '.',
        trim($_POST['price'] ?? '')
    );

    $stock = filter_var(
        $_POST['stock'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 0,
            ],
        ]
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $status = trim($_POST['status'] ?? '');
    $sizes = trim($_POST['sizes'] ?? '');

    $isFeatured = isset($_POST['is_featured'])
        ? 1
        : 0;

    $allowedCategories = [
        'pantalon',
        'tshirt',
        'hoodie',
        'short',
    ];

    $allowedStatuses = [
        'preorder',
        'stock',
    ];

    if (
        $name === ''
        || $slug === ''
        || $category === ''
        || $priceValue === ''
    ) {
        $error =
            'Merci de remplir les champs obligatoires.';
    } elseif (
        !in_array(
            $category,
            $allowedCategories,
            true
        )
    ) {
        $error = 'La catégorie sélectionnée est invalide.';
    } elseif (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {
        $error = 'Le statut sélectionné est invalide.';
    } elseif (
        !is_numeric($priceValue)
        || (float) $priceValue < 0
    ) {
        $error = 'Le prix renseigné est invalide.';
    } elseif ($stock === false) {
        $error = 'Le stock renseigné est invalide.';
    } else {
        $slugCheck = $pdo->prepare("
            SELECT id
            FROM products
            WHERE slug = ?
            AND id != ?
            LIMIT 1
        ");

        $slugCheck->execute([
            $slug,
            $productId,
        ]);

        if ($slugCheck->fetch()) {
            $error =
                'Un autre produit utilise déjà ce slug.';
        }
    }

    $oldImage = $product['image'] ?? '';
    $newProcessedImage = null;
    $image = $oldImage;

    if (
        $error === ''
        && !empty($_FILES['image']['name'])
    ) {
        try {
            $newProcessedImage =
                processProductImage(
                    $_FILES['image'],
                    $slug
                );

            $image = $newProcessedImage['image'];
        } catch (Throwable $exception) {
            error_log(
                'Erreur remplacement image produit : '
                . $exception->getMessage()
            );

            $error = $exception->getMessage();
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE products
                SET
                    name = ?,
                    slug = ?,
                    category = ?,
                    price = ?,
                    stock = ?,
                    description = ?,
                    status = ?,
                    sizes = ?,
                    image = ?,
                    is_featured = ?
                WHERE id = ?
            ");

            $update->execute([
                $name,
                $slug,
                $category,
                (float) $priceValue,
                (int) $stock,
                $description,
                $status,
                $sizes,
                $image,
                $isFeatured,
                $productId,
            ]);

            if ($newProcessedImage !== null) {
                $mainImageQuery = $pdo->prepare("
                    SELECT id
                    FROM product_images
                    WHERE product_id = ?
                    AND is_main = 1
                    LIMIT 1
                ");

                $mainImageQuery->execute([$productId]);

                $mainImageId =
                    $mainImageQuery->fetchColumn();

                if ($mainImageId) {
                    $updateMainImage = $pdo->prepare("
                        UPDATE product_images
                        SET image_path = ?
                        WHERE id = ?
                    ");

                    $updateMainImage->execute([
                        $image,
                        (int) $mainImageId,
                    ]);
                } else {
                    $insertMainImage = $pdo->prepare("
                        INSERT INTO product_images (
                            product_id,
                            image_path,
                            is_main,
                            sort_order
                        )
                        VALUES (?, ?, 1, 0)
                    ");

                    $insertMainImage->execute([
                        $productId,
                        $image,
                    ]);
                }
            }

            $pdo->commit();

            if (
                $newProcessedImage !== null
                && $oldImage !== ''
                && $oldImage !== $image
            ) {
                deleteProductImageSet($oldImage);
            }

            header('Location: products.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($newProcessedImage !== null) {
                deleteProductImageSet(
                    $newProcessedImage['image']
                );
            }

            error_log(
                'Erreur modification produit Below Dreams : '
                . $exception->getMessage()
            );

            $error =
                'Impossible de modifier le produit pour le moment.';
        }
    }

    $product = array_merge(
        $product,
        [
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'price' => $priceValue,
            'stock' => $stock === false
                ? 0
                : $stock,
            'description' => $description,
            'status' => $status,
            'sizes' => $sizes,
            'is_featured' => $isFeatured,
        ]
    );
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Modifier un produit</h1>

            <p>
                Modifiez les informations du produit sélectionné.
            </p>
        </div>

        <a
            href="products.php"
            class="admin-btn-secondary"
        >
            Retour aux produits
        </a>
    </header>

    <section class="admin-section">

        <?php if ($error !== '') : ?>
            <p class="admin-alert">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="admin-form"
        >

            <?= csrfField() ?>

            <div class="form-grid">

                <div class="form-group">
                    <label for="name">
                        Nom du produit *
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars(
                            $product['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="190"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="slug">Slug *</label>

                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        value="<?= htmlspecialchars(
                            $product['slug'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="190"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">
                        Catégorie *
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >
                        <option value="">Choisir</option>

                        <?php
                        $categories = [
                            'pantalon' => 'Pantalon',
                            'tshirt' => 'T-shirt',
                            'hoodie' => 'Hoodie',
                            'short' => 'Short',
                        ];
                        ?>

                        <?php foreach (
                            $categories
                            as $value => $label
                        ) : ?>
                            <option
                                value="<?= $value ?>"
                                <?= $product['category'] === $value
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Prix *</label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            (string) $product['price'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="stock">Stock *</label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        value="<?= (int) $product['stock'] ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="status">Statut</label>

                    <select id="status" name="status">
                        <option
                            value="preorder"
                            <?= $product['status'] === 'preorder'
                                ? 'selected'
                                : '' ?>
                        >
                            Précommande
                        </option>

                        <option
                            value="stock"
                            <?= $product['status'] === 'stock'
                                ? 'selected'
                                : '' ?>
                        >
                            Stock
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sizes">
                        Tailles disponibles
                    </label>

                    <input
                        type="text"
                        id="sizes"
                        name="sizes"
                        value="<?= htmlspecialchars(
                            $product['sizes'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="255"
                        placeholder="ex : S,M,L,XL"
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="10000"
                ><?= htmlspecialchars(
                    $product['description'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>
            </div>

            <div class="form-group">
                <label>Image principale actuelle</label>

                <?php if (!empty($product['image'])) : ?>
                    <img
                        src="../<?= htmlspecialchars(
                            $product['image'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="product-preview"
                        alt="<?= htmlspecialchars(
                            $product['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                <?php else : ?>
                    <span class="empty-image">
                        Aucune image
                    </span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="image">
                    Remplacer l’image principale
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <small>
                    L’image sera convertie en WebP et déclinée
                    automatiquement en trois tailles.
                </small>
            </div>

            <label class="checkbox-group">
                <input
                    type="checkbox"
                    name="is_featured"
                    <?= !empty($product['is_featured'])
                        ? 'checked'
                        : '' ?>
                >

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

<?php require_once __DIR__ . '/partials/footer.php'; ?>
