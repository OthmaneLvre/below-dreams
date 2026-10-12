<?php

require_once 'config/database.php';
require_once __DIR__ . '/includes/image-upload.php';

$query = $pdo->query("
    SELECT *
    FROM products
    WHERE is_active = 1
    ORDER BY id DESC
");

$products = $query->fetchAll(PDO::FETCH_ASSOC);

function normalizeProductCategory(string $category): string
{
    $category = strtolower(trim($category));

    return match ($category) {
        'pantalon', 'pantalons', 'pants' => 'pantalons',
        'short', 'shorts' => 'shorts',
        'hoodie', 'hoodies', 'sweat', 'sweats' => 'hoodies',
        'tshirt', 't-shirt', 'tshirts', 'tee-shirt', 'tee-shirts' => 'tshirts',
        'casquette', 'casquettes' => 'casquettes',
        'bonnet', 'bonnets' => 'bonnets',
        default => $category,
    };
}

$pageTitle =
    "Below Dreams | Vêtements Below Dreams";

$pageDescription =
    "Découvrez les vêtements et accessoires Below Dreams : "
    . "T-shirts, hoodies, pantalons, shorts, casquettes et bonnets. "
    . "Affirmez votre style avec notre collection streetwear.";

$pageCanonical =
    'https://belowdreams.com/shop.php';

$ogType = 'website';

$basePath = '';

require_once 'partials/header.php';
?>

<main>

    <section class="shop-hero" aria-label="Boutique Below Dreams">
        <div class="container shop-hero-inner">
            <h1 class="shop-title">Boutique</h1>
            <p class="shop-subtitle">
                Découvrez la collection Below Dreams. Affirmez votre style.
            </p>
        </div>
    </section>

    <section class="shop" aria-label="Catalogue produits">
        <div class="container shop-inner">

            <aside class="shop-filters" aria-label="Filtres">
                <h2 class="sr-only">Filtres</h2>

                <div class="filter-block">
                    <h3 class="filter-title">Catégorie</h3>
                    <ul class="filter-list">
                        <li><label class="filter-item"><input type="checkbox" name="category" value="pantalons"> Pantalons</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="category" value="shorts"> Shorts</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="category" value="hoodies"> Hoodies</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="category" value="tshirts"> T-shirts</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="category" value="casquettes"> Casquettes</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="category" value="bonnets"> Bonnets</label></li>
                    </ul>
                </div>

                <div class="filter-block">
                    <h3 class="filter-title">Disponibilité</h3>
                    <ul class="filter-list">
                        <li>
                            <label class="filter-item">
                                <input type="checkbox" name="availability" value="stock">
                                En stock
                            </label>
                        </li>
                        <li>
                            <label class="filter-item">
                                <input type="checkbox" name="availability" value="out-of-stock">
                                Rupture de stock
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="filter-block">
                    <h3 class="filter-title">Taille</h3>
                    <ul class="filter-list filter-sizes">
                        <li><label class="filter-chip"><input type="checkbox" name="size" value="xs"> XS</label></li>
                        <li><label class="filter-chip"><input type="checkbox" name="size" value="s"> S</label></li>
                        <li><label class="filter-chip"><input type="checkbox" name="size" value="m"> M</label></li>
                        <li><label class="filter-chip"><input type="checkbox" name="size" value="l"> L</label></li>
                        <li><label class="filter-chip"><input type="checkbox" name="size" value="xl"> XL</label></li>
                    </ul>
                </div>

                <button class="btn-primary shop-reset" type="button">
                    Réinitialiser
                </button>
            </aside>

            <section class="shop-results" aria-label="Résultats">
                <div class="shop-toolbar">
                    <p class="shop-count">
                        <span><?= count($products) ?></span> articles
                    </p>

                    <button
                        class="sort-btn"
                        id="sort-featured"
                        type="button"
                        aria-pressed="false"
                    >
                        Mis en avant
                    </button>

                    <label class="shop-sort">
                        <span class="sr-only">Trier</span>
                        <select id="sort-select" aria-label="Trier les produits">
                            <option value="default">Tri</option>
                            <option value="price-asc">Prix croissant</option>
                            <option value="price-desc">Prix décroissant</option>
                        </select>
                    </label>
                </div>

                <div class="shop-grid">

                    <?php foreach ($products as $product) : ?>

                        <?php
                        $productMediumImage = productImageVariantPath(
                            $product['image'] ?? null,
                            'medium'
                        );
                        ?>

                        <article
                            class="product-card"
                            data-featured="<?= (int) $product['is_featured'] === 1
                                ? 'true'
                                : 'false' ?>"
                            data-price="<?= htmlspecialchars(
                                (string) $product['price'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-category="<?= htmlspecialchars(
                                normalizeProductCategory($product['category']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-availability="<?= (int) $product['stock'] > 0
                                ? 'stock'
                                : 'out-of-stock'
                            ?>"
                            data-sizes="<?= htmlspecialchars(
                                strtolower($product['sizes'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <a
                                class="product-card__link"
                                href="product.php?slug=<?= urlencode($product['slug']) ?>"
                                aria-label="Voir le produit : <?= htmlspecialchars(
                                    $product['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                                <div class="product-card__media">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $productMediumImage ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        class="product-card__img"
                                        loading="lazy"
                                        decoding="async"
                                        width="800"
                                        height="800"
                                    >

                                    <?php if ((int) $product['stock'] <= 0) : ?>
                                        <span class="badge">
                                            Rupture de stock
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <div class="product-card__body">

                                    <h3 class="product-card__title">
                                        <?= htmlspecialchars(
                                            $product['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <p class="product-card__price">
                                        <?= number_format(
                                            (float) $product['price'],
                                            2,
                                            ',',
                                            ' '
                                        ) ?> €
                                    </p>

                                </div>

                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        </div>
    </section>

</main>

<script src="js/shop.js" defer></script>

<?php require_once 'partials/footer.php'; ?>
