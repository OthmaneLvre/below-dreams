<?php
require_once 'config/database.php';

$query = $pdo->query("
    SELECT *
    FROM products
    WHERE is_active = 1
      AND is_featured = 1
    ORDER BY id DESC
    LIMIT 4
");

$featuredProducts = $query->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Below Dreams | Accueil";
$pageDescription = "Below Dreams — pièces fortes, éditions limitées, précommande.";
$basePath = '';

require_once 'partials/header.php';
?>
  
    <main>
        <!-- HERO -->
        <section
            aria-label="Présentation de la marque"
            class="hero hero-video"
        >

            <video
                class="hero-video-media"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                aria-hidden="true"
            >
                <source
                    src="assets/videos/hero.webm"
                    type="video/webm"
                >

                <source
                    src="assets/videos/hero.mp4"
                    type="video/mp4"
                >
            </video>

            <div class="hero-video-overlay" aria-hidden="true"></div>

            <div class="hero-video-grain" aria-hidden="true"></div>

            <div class="hero-content">

                <h1 class="sr-only">
                    Below Dreams — Marque de vêtements en édition limitée
                </h1>

                <img
                    src="assets/logos/below-dreams-white.svg"
                    alt="Below Dreams"
                    class="hero-logo"
                >

                <p class="hero-text">
                    Des pièces fortes. Sans compromis.<br>
                    Éditions limitées. Précommande.
                </p>

                <a href="shop.php" class="btn-primary hero-cta">
                    Découvrir la collection
                </a>

            </div>

        </section>
        
        <!-- NOS ESSENTIELS -->
        <section aria-label="Nos essentiels" class="section-essentials">
            <div class="container">

                <h2 class="section-title">Nos essentiels</h2>

               <div class="products">

                    <?php foreach ($featuredProducts as $product) : ?>

                        <article class="product-card">
                            <a href="product.php?slug=<?= urlencode($product['slug']) ?>">
                                <img
                                    src="<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="product-image"
                                >

                                <h3 class="product-title">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h3>

                                <p class="product-price">
                                    <?= number_format($product['price'], 2, ',', ' ') ?> €
                                </p>

                                <span class="badge">
                                    <?= $product['status'] === 'preorder' ? 'Précommmande' : 'Stock' ?>
                                </span>
                            </a>
                        </article>

                    <?php endforeach; ?>

               </div>

            </div>
        
        </section>

        <!-- PRECOMMANDE -->
        <section aria-label="Précommande" class="section-preorder">
            <div class="container preorder-inner">

                <div class="preorder-card">
                    <h2 class="section-title">Fonctionnement en précommande</h2>
                    <p>
                        Les articles Below Dreams sont proposés en précommande. <br>
                        Expédition sous 2 à 3 semaines après validation de la commande.
                    </p>
                </div>
        
        </section>


        <!-- CTA -->
        <section aria-label="Accéder à la boutique" class="section-cta">
            <a href="shop.php" class="btn-primary">
                Découvrir la boutique
            </a>
        </section>

    </main>

<?php require_once 'partials/footer.php'; ?>