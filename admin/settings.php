<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Statistiques des modules
|--------------------------------------------------------------------------
|
| Pour le moment, seul le module Livraison est actif.
| Les autres modules seront activés progressivement.
|
*/

$modules = [
    [
        'name' => 'Livraison',
        'description' => 'Gère les transporteurs, les tarifs, les délais et les seuils de livraison offerte.',
        'icon' => 'fa-solid fa-truck-fast',
        'url' => 'shipping.php',
        'active' => true
    ],
    [
        'name' => 'Promotions',
        'description' => 'Configure les codes promotionnels, les remises et les conditions d’utilisation.',
        'icon' => 'fa-solid fa-tags',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Boutique',
        'description' => 'Modifie les informations générales et les paramètres commerciaux de la boutique.',
        'icon' => 'fa-solid fa-store',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Emails',
        'description' => 'Gère les emails automatiques envoyés aux clients pendant le parcours de commande.',
        'icon' => 'fa-solid fa-envelope',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Paiement',
        'description' => 'Consulte et configure les moyens de paiement disponibles sur la boutique.',
        'icon' => 'fa-solid fa-credit-card',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'SEO',
        'description' => 'Configure le référencement naturel, les balises et les informations sociales.',
        'icon' => 'fa-solid fa-magnifying-glass-chart',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Réseaux sociaux',
        'description' => 'Gère les liens vers les différents réseaux sociaux de Below Dreams.',
        'icon' => 'fa-solid fa-share-nodes',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Analytics',
        'description' => 'Configure les outils de suivi des visites, conversions et performances.',
        'icon' => 'fa-solid fa-chart-line',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Facturation',
        'description' => 'Configure les informations légales, fiscales et les documents commerciaux.',
        'icon' => 'fa-solid fa-file-invoice',
        'url' => null,
        'active' => false
    ],
    [
        'name' => 'Administrateurs',
        'description' => 'Gère les comptes administrateurs, les rôles et les permissions.',
        'icon' => 'fa-solid fa-user-shield',
        'url' => null,
        'active' => false
    ]
];

$totalModules = count($modules);

$activeModules = count(array_filter(
    $modules,
    static fn(array $module): bool => $module['active'] === true
));

$upcomingModules = $totalModules - $activeModules;

/*
|--------------------------------------------------------------------------
| Statistiques livraison
|--------------------------------------------------------------------------
*/

$shippingStatsQuery = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count
    FROM shipping_methods
");

$shippingStats = $shippingStatsQuery->fetch(PDO::FETCH_ASSOC);

$totalShippingMethods = (int) ($shippingStats['total'] ?? 0);
$activeShippingMethods = (int) ($shippingStats['active_count'] ?? 0);

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header">
        <h1>Centre de configuration</h1>

        <p>
            Configure les fonctionnalités et les paramètres généraux
            de la boutique Below Dreams.
        </p>
    </header>

    <section class="settings-stats-grid">

        <article class="admin-stat-card settings-stat-card">
            <div class="settings-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <div>
                <span>Modules disponibles</span>
                <strong><?= $totalModules ?></strong>
            </div>
        </article>

        <article class="admin-stat-card settings-stat-card">
            <div class="settings-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Modules actifs</span>
                <strong><?= $activeModules ?></strong>
            </div>
        </article>

        <article class="admin-stat-card settings-stat-card">
            <div class="settings-stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <span>Modules à venir</span>
                <strong><?= $upcomingModules ?></strong>
            </div>
        </article>

        <article class="admin-stat-card settings-stat-card">
            <div class="settings-stat-icon">
                <i class="fa-solid fa-truck"></i>
            </div>

            <div>
                <span>Livraisons actives</span>

                <strong>
                    <?= $activeShippingMethods ?>
                    <small>/ <?= $totalShippingMethods ?></small>
                </strong>
            </div>
        </article>

    </section>

    <section class="settings-section-header">
        <div>
            <h2>Modules de la boutique</h2>

            <p>
                Active et configure progressivement les fonctionnalités
                nécessaires au fonctionnement de la boutique.
            </p>
        </div>
    </section>

    <section class="settings-grid">

        <?php foreach ($modules as $module) : ?>

            <?php if ($module['active'] && !empty($module['url'])) : ?>

                <article class="settings-card">

                    <div class="settings-card-icon">
                        <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                    </div>

                    <div class="settings-card-content">

                        <div class="settings-card-heading">
                            <h2><?= htmlspecialchars($module['name']) ?></h2>

                            <span class="settings-module-badge settings-module-active">
                                Actif
                            </span>
                        </div>

                        <p>
                            <?= htmlspecialchars($module['description']) ?>
                        </p>

                        <div class="settings-card-footer">
                            <span class="settings-card-summary">
                                <?= $activeShippingMethods ?>
                                mode<?= $activeShippingMethods > 1 ? 's' : '' ?>
                                actif<?= $activeShippingMethods > 1 ? 's' : '' ?>
                            </span>

                            <a
                                href="<?= htmlspecialchars($module['url']) ?>"
                                class="admin-btn admin-btn-small"
                            >
                                Gérer

                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>

                    </div>

                </article>

            <?php else : ?>

                <article class="settings-card settings-card-disabled">

                    <div class="settings-card-icon">
                        <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                    </div>

                    <div class="settings-card-content">

                        <div class="settings-card-heading">
                            <h2><?= htmlspecialchars($module['name']) ?></h2>

                            <span class="settings-module-badge settings-module-upcoming">
                                À venir
                            </span>
                        </div>

                        <p>
                            <?= htmlspecialchars($module['description']) ?>
                        </p>

                        <div class="settings-card-footer">
                            <span class="settings-card-status">
                                Bientôt disponible
                            </span>
                        </div>

                    </div>

                </article>

            <?php endif; ?>

        <?php endforeach; ?>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>