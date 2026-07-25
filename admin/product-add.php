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

$pageTitle = 'Ajouter un produit | Below Dreams';

$error = '';

$formValues = [
    'name' => '',
    'slug' => '',
    'category' => '',
    'price' => '',
    'stock' => '0',
    'description' => '',
    'status' => 'preorder',
    'sizes' => '',
];

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

    $formValues = [
        'name' => $name,
        'slug' => $slug,
        'category' => $category,
        'price' => $priceValue,
        'stock' => $stock === false ? '0' : (string) $stock,
        'description' => $description,
        'status' => $status,
        'sizes' => $sizes,
    ];

    if (
        $name === ''
        || $slug === ''
        || $category === ''
        || $priceValue === ''
    ) {
        $error =
            'Merci de remplir les champs obligatoires.';
    } elseif (mb_strlen($name) > 190) {
        $error = 'Le nom du produit est trop long.';
    } elseif (mb_strlen($slug) > 190) {
        $error = 'Le slug du produit est trop long.';
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
    } elseif (mb_strlen($description) > 10000) {
        $error = 'La description est trop longue.';
    } else {
        $slugCheck = $pdo->prepare("
            SELECT id
            FROM products
            WHERE slug = ?
            LIMIT 1
        ");

        $slugCheck->execute([$slug]);

        if ($slugCheck->fetch()) {
            $error =
                'Un produit utilise déjà ce slug.';
        }
    }

    $processedImages = [];

    if (
        $error === ''
        && !empty($_FILES['images']['name'][0])
    ) {
        $fileCount = count(
            $_FILES['images']['name']
        );

        if ($fileCount > 8) {
            $error =
                'Vous ne pouvez pas envoyer plus de 8 images.';
        } else {
            try {
                foreach (
                    $_FILES['images']['name']
                    as $index => $originalName
                ) {
                    if ($originalName === '') {
                        continue;
                    }

                    $uploadedFile = normalizeUploadedFile(
                        $_FILES['images'],
                        $index
                    );

                    $processedImages[] =
                        processProductImage(
                            $uploadedFile,
                            $slug
                        );
                }
            } catch (Throwable $exception) {
                foreach ($processedImages as $processedImage) {
                    deleteProductImageSet(
                        $processedImage['image']
                    );
                }

                $processedImages = [];

                error_log(
                    'Erreur upload produit Below Dreams : '
                    . $exception->getMessage()
                );

                $error = $exception->getMessage();
            }
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $mainImage = !empty($processedImages)
                ? $processedImages[0]['image']
                : '';

            $query = $pdo->prepare("
                INSERT INTO products (
                    name,
                    slug,
                    category,
                    price,
                    stock,
                    description,
                    status,
                    sizes,
                    image,
                    is_featured
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $query->execute([
                $name,
                $slug,
                $category,
                (float) $priceValue,
                (int) $stock,
                $description,
                $status,
                $sizes,
                $mainImage,
                $isFeatured,
            ]);

            $productId = (int) $pdo->lastInsertId();

            if (!empty($processedImages)) {
                $imageQuery = $pdo->prepare("
                    INSERT INTO product_images (
                        product_id,
                        image_path,
                        is_main,
                        sort_order
                    )
                    VALUES (?, ?, ?, ?)
                ");

                foreach (
                    $processedImages
                    as $index => $processedImage
                ) {
                    $imageQuery->execute([
                        $productId,
                        $processedImage['image'],
                        $index === 0 ? 1 : 0,
                        $index,
                    ]);
                }
            }

            $pdo->commit();

            header('Location: products.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach ($processedImages as $processedImage) {
                deleteProductImageSet(
                    $processedImage['image']
                );
            }

            error_log(
                'Erreur création produit Below Dreams : '
                . $exception->getMessage()
            );

            $error =
                'Impossible d’ajouter le produit pour le moment.';
        }
    }
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Ajouter un produit</h1>

            <p>
                Ajoutez un nouveau produit à la boutique Below Dreams.
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
                            $formValues['name'],
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
                            $formValues['slug'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="190"
                        placeholder="ex : tshirt-oversize-unisexe"
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
                                <?= $formValues['category'] === $value
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
                        value="<?= htmlspecialchars(
                            $formValues['price'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="stock">Stock *</label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        value="<?= htmlspecialchars(
                            $formValues['stock'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        min="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="status">Statut</label>

                    <select id="status" name="status">
                        <option
                            value="preorder"
                            <?= $formValues['status'] === 'preorder'
                                ? 'selected'
                                : '' ?>
                        >
                            Précommande
                        </option>

                        <option
                            value="stock"
                            <?= $formValues['status'] === 'stock'
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
                            $formValues['sizes'],
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
                    $formValues['description'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>
            </div>

            <div class="form-group">
                <label for="images">
                    Images du produit
                </label>

                <input
                    type="file"
                    id="images"
                    name="images[]"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    multiple
                >

                <small>
                    Maximum 8 images et 10 Mo par fichier.
                    La première sera l’image principale.
                    Les images seront automatiquement converties en WebP.
                </small>
            </div>

            <label class="checkbox-group">
                <input
                    type="checkbox"
                    name="is_featured"
                    <?= isset($_POST['is_featured'])
                        ? 'checked'
                        : '' ?>
                >

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

<?php require_once __DIR__ . '/partials/footer.php'; ?>
