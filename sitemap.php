<?php

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = 'https://belowdreams.com';

$staticPages = [
    [
        'loc' => '/',
        'changefreq' => 'weekly',
        'priority' => '1.0',
    ],
    [
        'loc' => '/shop.php',
        'changefreq' => 'daily',
        'priority' => '0.9',
    ],
    [
        'loc' => '/contact.php',
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => '/mentions-legales.php',
        'changefreq' => 'yearly',
        'priority' => '0.2',
    ],
    [
        'loc' => '/cgv.php',
        'changefreq' => 'yearly',
        'priority' => '0.3',
    ],
    [
        'loc' => '/politique-confidentialite.php',
        'changefreq' => 'yearly',
        'priority' => '0.2',
    ],
    [
        'loc' => '/politique-cookies.php',
        'changefreq' => 'yearly',
        'priority' => '0.2',
    ],
    [
        'loc' => '/retractation.php',
        'changefreq' => 'yearly',
        'priority' => '0.2',
    ],
];

$productQuery = $pdo->query("
    SELECT
        slug,
        updated_at,
        created_at
    FROM products
    WHERE is_active = 1
    ORDER BY id DESC
");

$products = $productQuery->fetchAll(PDO::FETCH_ASSOC);

$xmlEscape = static function (string $value): string {
    return htmlspecialchars(
        $value,
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($staticPages as $page) : ?>
    <url>
        <loc><?= $xmlEscape($baseUrl . $page['loc']) ?></loc>
        <changefreq><?= $page['changefreq'] ?></changefreq>
        <priority><?= $page['priority'] ?></priority>
    </url>
<?php endforeach; ?>

<?php foreach ($products as $product) : ?>
    <?php
    $lastModified = $product['updated_at']
        ?? $product['created_at']
        ?? null;
    ?>

    <url>
        <loc><?= $xmlEscape(
            $baseUrl
            . '/product.php?slug='
            . rawurlencode($product['slug'])
        ) ?></loc>

        <?php if (!empty($lastModified)) : ?>
            <lastmod><?= date(
                'Y-m-d',
                strtotime($lastModified)
            ) ?></lastmod>
        <?php endif; ?>

        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>

</urlset>