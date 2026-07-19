<?php
require_once 'config/database.php';

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: shop.php');
    exit;
}

$slug = $_GET['slug'];

$query = $pdo->prepare("
    SELECT *
    FROM products
    WHERE slug = ?
    AND is_active = 1
    LIMIT 1
");

$query->execute([$slug]);
$product = $query->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: shop.php');
    exit;
}

$sizes = array_filter(array_map('trim', explode(',', $product['sizes'])));

$imagesQuery = $pdo->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");

$imagesQuery->execute([$product['id']]);
$productImages = $imagesQuery->fetchAll(PDO::FETCH_COLUMN);

if (empty($productImages) && !empty($product['image'])) {
    $productImages[] = $product['image'];
}

$mainImage = $productImages[0] ?? $product['image'];

$isAvailable = $product['status'] === 'preorder'
    || (int) $product['stock'] > 0;

$pageTitle = 
    $product['name'] . ' | Below Dreams';

$pageDescription = !empty($product['description'])
    ? mb_substr(
        strip_tags($product['description']),
        0,
        155
    )
    : 'Découvrez '
        . $product['name']
        . ', une pièce Below Dreams en édition limitée.';

$pageCanonical =
    'htpps://belowdreams.com/product.php?slug='
    . rawurlencode($product['slug']);

$ogType = 'product';

$ogImage = !empty($product['image'])
    ? 'https://belowdreams.com/'
        . ltrim($product['image'], '/')
    : 'https://belowdreams.com/assets/logos/below-dreams-social.jpg';
    
$basePath = '';

require_once 'partials/header.php';
?>

    <main>
        <section class="product-detail">
            <div class="container product-detail-inner">

                <div class="product-detail-media">
                    <img
                            src="<?= htmlspecialchars($mainImage) ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="product-detail-img"
                            id="main-product-image"
                    >

                        <?php if (count($productImages) > 1) : ?>
                            <div class="product-gallery-thumbs">

                                <?php foreach ($productImages as $index => $imagePath) : ?>
                                    <button
                                        type="button"
                                        class="product-gallery-thumb <?= $index === 0 ? 'active' : '' ?>"
                                        data-image="<?= htmlspecialchars($imagePath) ?>"
                                        aria-label="Voir l'image <?= $index + 1 ?>"
                                    >
                                        <img
                                            src="<?= htmlspecialchars($imagePath) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?> image <?= $index + 1 ?>"
                                        >
                                    </button>
                                <?php endforeach; ?>

                            </div>
                        <?php endif; ?>
                
                </div>

                <div class="product-detail-content">
                    <span class="badge badge--preorder">
                        <?= htmlspecialchars($product['status']) === 'preorder' ? 'Précommande' : 'Stock' ?>
                    </span>

                    <h1 class="product-detail-title">
                        <?= htmlspecialchars($product['name']) ?>
                    </h1>

                    <p class="product-detail-price">
                        <?= number_format($product['price'], 2, ',', ' ') ?> €
                    </p>

                    <p class="product-detail-text">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </p>

                    <div class="product-option">
                        <h2>Taille</h2>
                        <div class="product-sizes">
                            <?php foreach ($sizes as $size) : ?>
                                <label>
                                    <input type="radio" name="size" value="<?= htmlspecialchars($size) ?>">
                                    <?= htmlspecialchars($size) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="product-option">
                        <h2>Quantité</h2>
                        <input class="product-quantity" type="number" value="1" min="1">
                    </div>

                    <?php if ($product['status'] === 'preorder') : ?>

                        <p class="product-stock product-stock-preorder">
                            📦 Disponible en précommande
                        </p>

                    <?php elseif ((int) $product['stock'] > 0) : ?>

                        <p class="product-stock product-stock-ok">
                            ✅ En stock (<?= (int) $product['stock'] ?>)
                        </p>

                    <?php else : ?>

                        <p class="product-stock product-stock-ko">
                            ❌ Rupture de stock
                        </p>

                    <?php endif; ?>

                    <button 
                        class="btn-primary add-to-cart"
                        type="button"
                        <?= !$isAvailable ? 'disabled' : '' ?>
                        data-id="<?= (int) $product['id'] ?>"
                        data-slug="<?= htmlspecialchars($product['slug']) ?>"
                        data-name="<?= htmlspecialchars($product['name']) ?>"
                        data-price="<?= htmlspecialchars($product['price']) ?>"
                        data-image="<?= htmlspecialchars($mainImage) ?>"
                        data-status="<?= htmlspecialchars($product['status']) ?>"
                    >
                        <?= $isAvailable ? 'Ajouter au panier' : 'Rupture de stock' ?>
                    </button>
                </div>

            </div>
        </section>
    </main>

<script>
document.querySelectorAll('.product-gallery-thumb').forEach(function (button) {
    button.addEventListener('click', function () {
        const mainImage = document.getElementById('main-product-image');

        if (!mainImage) {
            return;
        }

        mainImage.src = this.dataset.image;

        document.querySelectorAll('.product-gallery-thumb').forEach(function (thumb) {
            thumb.classList.remove('active');
        });

        this.classList.add('active');
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>