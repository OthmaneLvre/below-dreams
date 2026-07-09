<?php

require_once 'config/database.php';

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
        default => $category,
    };
}

function normalizeProductStatus(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'preoder', 'preorder', 'précommande', 'precommande' => 'preorder',
        'stock' => 'stock',
        default => $status,
    };
}

$pageTitle = "Below Dreams | Boutique";
$pageDescription = "Découvrez toutes les pièces Below Dreams en édition limitée.";
$basePath = '';

require_once 'partials/header.php';
?>

<main>

    <section class="shop-hero" aria-label="Boutique Below Dreams">
        <div class="container shop-hero-inner">
            <h1 class="shop-title">Boutique</h1>
            <p class="shop-subtitle">
                Éditions limitées, précommande. Expédition sous 2 à 3 semaines.
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
                    </ul>
                </div>

                <div class="filter-block">
                    <h3 class="filter-title">Disponibilité</h3>
                    <ul class="filter-list">
                        <li><label class="filter-item"><input type="checkbox" name="availability" value="preorder"> Précommande</label></li>
                        <li><label class="filter-item"><input type="checkbox" name="availability" value="stock"> Stock</label></li>
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

                        <article
                            class="product-card"
                            data-featured="<?= (int) $product['is_featured'] === 1 ? 'true' : 'false' ?>"
                            data-price="<?= htmlspecialchars((string) $product['price']) ?>"
                            data-category="<?= htmlspecialchars(normalizeProductCategory($product['category'])) ?>"
                            data-availability="<?= htmlspecialchars(normalizeProductStatus($product['status'])) ?>"
                            data-sizes="<?= htmlspecialchars(strtolower($product['sizes'])) ?>"
                        >

                            <a
                                class="product-card__link"
                                href="product.php?slug=<?= urlencode($product['slug']) ?>"
                                aria-label="Voir le produit : <?= htmlspecialchars($product['name']) ?>"
                            >

                                <div class="product-card__media">

                                    <img
                                        src="<?= htmlspecialchars($product['image']) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        class="product-card__img"
                                    >

                                    <span class="badge badge--preorder">
                                        <?= htmlspecialchars($product['status']) ?>
                                    </span>

                                </div>

                                <div class="product-card__body">
                                    <h3 class="product-card__title">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h3>

                                    <p class="product-card__price">
                                        <?= number_format((float) $product['price'], 2, ',', ' ') ?> €
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