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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Below Dreams | Produit</title>
    <meta name="description" content="Découvrez le détail d'un produit Below Dreams en édition limitée et précommande.">

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
    <script src="js/cart.js" defer></script>
</body>
</html>