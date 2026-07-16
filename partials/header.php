<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Below Dreams' ?></title>
    <meta name="description" content="<?= $pageDescription ?? 'Below Dreams — pièces fortes, éditions limitées, précommande.' ?>">

    <link rel="stylesheet" href="<?= $basePath ?? '' ?>css/style.css">
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>css/responsive.css">

</head>

<body>
    
<header class="site-header">
    <div class="container header-inner">

        <a href="<?= $basePath ?? '' ?>index.php" class="brand" aria-label="Below Dreams">
            <img src="<?= $basePath ?? '' ?>assets/logos/below-dreams-white.svg" alt="Below Dreams" class="brand-logo">
        </a>

        <nav class="nav" aria-label="Navigation principale">

            <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-menu">
                <span class="nav-toggle-bar" aria-hidden="true"></span>
                <span class="nav-toggle-bar" aria-hidden="true"></span>
                <span class="nav-toggle-bar" aria-hidden="true"></span>
            </button>

            <div class="nav-menu" id="nav-menu">
                <a href="<?= $basePath ?? '' ?>shop.php" class="nav-link">Boutique</a>
                <a href="<?= $basePath ?? '' ?>contact.php" class="nav-link">Contact</a>

                <?php if (isset($_SESSION['customer_id'])) : ?>

                <div class="nav-user-dropdown">

                    <?php
                    $customerFirstname = $_SESSION['customer_firstname'] ?? '';
                    ?>

                    <button class="nav-user-button" type="button">
                        Bonjour <?= htmlspecialchars($customerFirstname) ?> ▼
                    </button>

                    <div class="nav-user-menu">

                        <a href="<?= $basePath ?? '' ?>account/dashboard.php">
                            Mon compte
                        </a>

                        <a href="<?= $basePath ?? '' ?>account/orders.php">
                            Mes commandes
                        </a>

                        <a href="<?= $basePath ?? '' ?>account/logout.php" class="nav-link-logout">
                            Déconnexion
                        </a>

                    </div>

                </div>

                <?php else : ?>

                    <a href="<?= $basePath ?? '' ?>account/login.php" class="nav-link">
                        Connexion
                    </a>

                    <a href="<?= $basePath ?? '' ?>account/register.php" class="nav-link nav-link-register">
                        Inscription
                    </a>

                <?php endif; ?>
            </div>

            <a href="<?= $basePath ?? '' ?>cart.php" class="nav-cart" aria-label="Panier">
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