<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security-headers.php';

/*
|--------------------------------------------------------------------------
| SEO — valeurs par défaut
|--------------------------------------------------------------------------
*/

$siteName = 'Below Dreams';
$siteUrl = 'https://belowdreams.com';

$defaultTitle = 'Below Dreams | Vêtements en édition limitée';
$defaultDescription =
    'Découvrez Below Dreams, une marque de vêtements proposant '
    . 'des pièces fortes en édition limitée et en précommande.';

$pageTitle = $pageTitle ?? $defaultTitle;
$pageDescription = $pageDescription ?? $defaultDescription;

$pageRobots = $pageRobots ?? 'index, follow';
$pageCanonical = $pageCanonical ?? null;

$ogType = $ogType ?? 'website';
$ogImage = $ogImage
    ?? $siteUrl . '/assets/logos/below-dreams-social.png';

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$currentPath = is_string($currentPath)
    ? $currentPath
    : '/';

/*
|--------------------------------------------------------------------------
| Nettoyage du chemin local
|--------------------------------------------------------------------------
|
| En local :
| /belowdreams/index.php
|
| En production :
| /index.php
|
| Le canonical doit toujours utiliser l’URL publique.
|
*/

$localBasePath = '/belowdreams';

if (
    str_starts_with(
        mb_strtolower($currentPath),
        $localBasePath
    )
) {
    $currentPath = substr(
        $currentPath,
        strlen($localBasePath)
    );

    if ($currentPath === '') {
        $currentPath = '/';
    }
}

if ($pageCanonical === null) {
    if (
        $currentPath === '/'
        || $currentPath === '/index.php'
    ) {
        $pageCanonical = $siteUrl . '/';
    } else {
        $pageCanonical = $siteUrl . $currentPath;
    }
}

$escape = static function (?string $value): string {
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
};

?>
<!DOCTYPE html>
<html lang="fr">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= $escape($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= $escape($pageDescription) ?>"
    >

    <meta
        name="robots"
        content="<?= $escape($pageRobots) ?>"
    >

    <link
        rel="canonical"
        href="<?= $escape($pageCanonical) ?>"
    >

    <!-- Fonts -->
    <link
        rel="preload"
        href="assets/fonts/oswald/Oswald-Bold.woff2"
        as="font"
        type="font/woff2"
        crossorigin
    >

    <link
        rel="preload"
        href="assets/fonts/inter/Inter-Regular.woff2"
        as="font"
        type="font/woff2"
        crossorigin
    >

    <!-- Open Graph -->

    <meta
        property="og:locale"
        content="fr_FR"
    >

    <meta
        property="og:type"
        content="<?= $escape($ogType) ?>"
    >

    <meta
        property="og:site_name"
        content="<?= $escape($siteName) ?>"
    >

    <meta
        property="og:title"
        content="<?= $escape($pageTitle) ?>"
    >

    <meta
        property="og:description"
        content="<?= $escape($pageDescription) ?>"
    >

    <meta
        property="og:url"
        content="<?= $escape($pageCanonical) ?>"
    >

    <meta
        property="og:image"
        content="<?= $escape($ogImage) ?>"
    >

    <meta
        property="og:image:alt"
        content="<?= $escape($pageTitle) ?>"
    >

    <!-- X / Twitter -->

    <meta
        name="twitter:card"
        content="summary_large_image"
    >

    <meta
        name="twitter:title"
        content="<?= $escape($pageTitle) ?>"
    >

    <meta
        name="twitter:description"
        content="<?= $escape($pageDescription) ?>"
    >

    <meta
        name="twitter:image"
        content="<?= $escape($ogImage) ?>"
    >

    <!-- Apparence navigateur -->

    <meta
        name="theme-color"
        content="#0A0A0A"
    >

    <!-- Favicons -->

    <link
        rel="icon"
        type="image/x-icon"
        href="<?= $basePath ?? '' ?>assets/logos/favicon-belowDreams.ico"
    >

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="<?= $basePath ?? '' ?>assets/logos/favicon-32x32.png"
    >

    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="<?= $basePath ?? '' ?>assets/logos/favicon-16x16.png"
    >

    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="<?= $basePath ?? '' ?>assets/logos/apple-touch-icon.png"
    >

    <link
        rel="manifest"
        href="<?= $basePath ?? '' ?>assets/logos/site.webmanifest"
    >

    <!-- CSS -->

    <link
        rel="stylesheet"
        href="<?= $basePath ?? '' ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= $basePath ?? '' ?>css/responsive.css"
    >

    <!-- Balise HTML -->
    <meta
        name="google-site-verification"
        content="E9arOm42bGCDUtWqja0gg4hKNiXwB-0Ain2pmKmgOD8"
    />

    <script type="application/ld+json">
        <?= json_encode(
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'Below Dreams',
                'url' => 'https://belowdreams.com',
                'logo' =>
                    'https://belowdreams.com/assets/logos/'
                    . 'below-dreams-black.svg',
                'email' => 'contact@belowdreams.com'
            ],
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        ) ?>
    </script>

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
