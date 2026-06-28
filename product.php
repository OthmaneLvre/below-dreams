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

$pageTitle = "Below Dreams | " . $product['name'];
$pageDescription = substr(strip_tags($product['description']), 0, 160);
$basePath = '';

require_once 'partials/header.php';
?>

    <main>
        <section class="product-detail">
            <div class="container product-detail-inner">

                <div class="product-detail-media">
                    <img 
                        src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="product-detail-img"
                    >
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

                    <button 
                        class="btn-primary add-to-cart"
                        type="button"
                        data-id="<?= $product['id'] ?>"
                        data-name="<?= htmlspecialchars($product['name']) ?>"
                        data-price="<?= $product['price'] ?>"
                        data-image="<?= htmlspecialchars($product['image']) ?>"
                    >
                        Ajouter au panier
                    </button>
                </div>

            </div>
        </section>
    </main>

<?php require_once 'partials/footer.php'; ?>