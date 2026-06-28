<?php

require_once 'config/database.php';

$query = $pdo->query("
    SELECT *
    FROM products
    WHERE is_active = 1
    ORDER BY is_featured DESC, id DESC
");

$products = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Below Dreams | Accueil</title>
    <meta name="description" content="Below Dreams — pièces fortes, éditions limitées, précommande.">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>

<body>
    
    <!-- HEADER : Logo + Nav + Panier -->
    <header class="site-header">
        <div class="container header-inner">

            <!-- Logo à gauche -->
            <a href="index.php" class="brand" aria-label="Below Dreams">
                <img src="assets/logos/below-dreams-white.svg" alt="Below Dreams" class="brand-logo">
            </a>

            <nav class="nav" aria-label="Navigation principale">

                <!-- Bouton burger (mobile) -->
                <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-menu">
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                </button>

                <!-- Menu -->
                <div class="nav-menu" id="nav-menu">
                    <a href="shop.php" class="nav-link">Boutique</a>
                    <a href="contact.html" class="nav-link">Contact</a>
                    <a href="compte.html" class="nav-link">Mon Compte</a>
                </div>

                
                <!-- Panier (reste visible) -->
                <a href="cart.php" class="nav-cart" aria-label="Panier">
                    <svg
                        class="icon-cart"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        />
                    </svg>

                    <span class="cart-count" aria-label="Nombre d'articles dans le panier">0</span>

                </a>
            </nav>
        </div>

    </header>
    
    <main>

        <!-- HERO BOUTIQUE (intro courte) -->
        <section class="shop-hero" aria-label="Boutique Below Dreams">
            <div class="container shop-hero-inner">
                <h1 class="shop-title">Boutique</h1>
                <p class="shop-subtitle">
                Éditions limitées, précommande. Expédition sous 2 à 3 semaines.
                </p>
            </div>
        </section>

        <!-- CONTENU BOUTIQUE -->
        <section class="shop" aria-label="Catalogue produits">
            <div class="container shop-inner">

                <!-- FILTRES (structure seulement pour l’instant) -->
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

                <button class="btn-primary shop-reset" type="button">Réinitialiser</button>
                </aside>

                    <!-- LISTING PRODUITS -->
                <section class="shop-results" aria-label="Résultats">
                    <div class="shop-toolbar">
                        <p class="shop-count">
                            <span><?= count($products) ?></span> articles
                        </p>

                        <!-- Bouton toggle : Mis en avant -->
                        <button
                            class="sort-btn"
                            id="sort-featured"
                            type="button"
                            aria-pressed="false"
                        >
                            Mis en avant
                        </button>

                    <!-- Select : tri prix -->
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
                        <!-- PRODUCT CARD -->
                        <?php foreach ($products as $product) : ?>

                        <article
                            class="product-card"
                            data-featured="<?= $product['is_featured'] ? 'true' : 'false' ?>"
                            data-price="<?= $product['price'] ?>"
                            data-category="<?= htmlspecialchars($product['category']) ?>"
                            data-availability="<?= htmlspecialchars($product['status']) ?>"
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
                                        <?= number_format($product['price'], 2, ',', ' ') ?> €
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

    
    <!-- ================= FOOTER ================= -->
    <footer class="site-footer">
        <div class="container footer-inner">

            <!-- Ligne haute -->
            <div class="footer-top">
                <img src="assets/logos/below-dreams-white.svg"
                    alt="Below Dreams" 
                    class="footer-logo"    
                >

                <nav class="footer-nav" aria-label="Liens légaux">
                    <a href="docs/mentions-legales.html">Mentions légales</a>
                    <a href="docs/cgv.html">CGV</a>
                    <a href="docs/politique-confidentialite.html">Politique de confidentialité</a>
                    <a href="contact.html">Contact</a>
                </nav>
            </div>

            <!-- Ligne basse -->
            <div class="footer-bottom">
                <p>© Below Dreams — Tous droits réservés</p>
                <p>
                    Développé par 
                    <a href="https://olcreativestudio.fr" target="_blank" rel="noopener noreferrer">
                        OL Creative Studio
                    </a>
                </p>
            </div>
        
        </div>
    </footer>

    <script src="js/main.js" defer></script>
    <script src="js/shop.js" defer></script>
    <script src="js/cart.js" defer></script>
</body>
</html>