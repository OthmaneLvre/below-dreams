<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Liste des modes de livraison
|--------------------------------------------------------------------------
*/

$query = $pdo->query("
    SELECT *
    FROM shipping_methods
    ORDER BY sort_order ASC, id ASC
");

$shippingMethods = $query->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$totalShippingMethods = count($shippingMethods);

$activeShippingMethods = count(array_filter(
    $shippingMethods,
    static fn(array $method): bool => (int) $method['is_active'] === 1
));

$inactiveShippingMethods = $totalShippingMethods - $activeShippingMethods;

$freeShippingMethods = count(array_filter(
    $shippingMethods,
    static function (array $method): bool {
        return (float) $method['price'] === 0.0
            || (
                $method['free_shipping_threshold'] !== null
                && $method['free_shipping_threshold'] !== ''
            );
    }
));

$deliveryTypeLabels = [
    'home' => 'À domicile',
    'relay' => 'Point relais',
    'pickup' => 'Retrait sur place',
    'other' => 'Autre'
];

$deliveryTypeIcons = [
    'home' => 'fa-solid fa-house',
    'relay' => 'fa-solid fa-location-dot',
    'pickup' => 'fa-solid fa-store',
    'other' => 'fa-solid fa-box'
];

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">

        <div>
            <a href="settings.php" class="admin-back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux paramètres
            </a>

            <h1>Gestion de la livraison</h1>

            <p>
                Configure les transporteurs, les frais, les délais
                et les conditions de livraison offerte.
            </p>
        </div>

        <a href="shipping-add.php" class="admin-btn">
            <i class="fa-solid fa-plus"></i>
            Ajouter un mode de livraison
        </a>

    </header>

    <?php if (isset($_GET['created'])) : ?>
        <div class="admin-success">
            <i class="fa-solid fa-circle-check"></i>
            Le mode de livraison a bien été ajouté.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])) : ?>
        <div class="admin-success">
            <i class="fa-solid fa-circle-check"></i>
            Le mode de livraison a bien été modifié.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])) : ?>
        <div class="admin-success">
            <i class="fa-solid fa-circle-check"></i>
            Le mode de livraison a bien été supprimé.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['delete_error'])) : ?>
        <div class="admin-alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            Ce mode de livraison ne peut pas être supprimé.
            Désactive-le plutôt afin de conserver l’historique des commandes.
        </div>
    <?php endif; ?>

    <section class="shipping-stats-grid">

        <article class="admin-stat-card shipping-stat-card">

            <div class="shipping-stat-icon">
                <i class="fa-solid fa-truck-fast"></i>
            </div>

            <div>
                <span>Total des modes</span>
                <strong><?= $totalShippingMethods ?></strong>
            </div>

        </article>

        <article class="admin-stat-card shipping-stat-card">

            <div class="shipping-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Modes actifs</span>
                <strong><?= $activeShippingMethods ?></strong>
            </div>

        </article>

        <article class="admin-stat-card shipping-stat-card">

            <div class="shipping-stat-icon">
                <i class="fa-solid fa-circle-pause"></i>
            </div>

            <div>
                <span>Modes inactifs</span>
                <strong><?= $inactiveShippingMethods ?></strong>
            </div>

        </article>

        <article class="admin-stat-card shipping-stat-card">

            <div class="shipping-stat-icon">
                <i class="fa-solid fa-gift"></i>
            </div>

            <div>
                <span>Gratuité disponible</span>
                <strong><?= $freeShippingMethods ?></strong>
            </div>

        </article>

    </section>

    <section class="admin-section">

        <div class="shipping-section-header">

            <div>
                <h2>Modes de livraison</h2>

                <p class="admin-muted">
                    Les modes actifs seront proposés aux clients
                    pendant la validation de leur commande.
                </p>
            </div>

            <a href="shipping-add.php" class="admin-btn admin-btn-small">
                <i class="fa-solid fa-plus"></i>
                Ajouter
            </a>

        </div>

        <?php if (empty($shippingMethods)) : ?>

            <div class="admin-empty-state">

                <div class="admin-empty-state-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>

                <h2>Aucun mode de livraison</h2>

                <p>
                    Ajoute un premier transporteur afin que les clients
                    puissent choisir leur mode de livraison au moment
                    de finaliser leur commande.
                </p>

                <a href="shipping-add.php" class="admin-btn">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter un mode de livraison
                </a>

            </div>

        <?php else : ?>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Mode de livraison</th>
                            <th>Transporteur</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Offerte dès</th>
                            <th>Délai</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($shippingMethods as $shippingMethod) : ?>

                            <?php
                            $deliveryType = $shippingMethod['delivery_type'] ?? 'other';

                            $deliveryTypeLabel = $deliveryTypeLabels[$deliveryType]
                                ?? 'Autre';

                            $deliveryTypeIcon = $deliveryTypeIcons[$deliveryType]
                                ?? 'fa-solid fa-box';

                            $isActive = (int) $shippingMethod['is_active'] === 1;

                            $price = (float) $shippingMethod['price'];

                            $freeThreshold = $shippingMethod['free_shipping_threshold'];
                            ?>

                            <tr>

                                <td>
                                    <span class="shipping-order-number">
                                        <?= (int) $shippingMethod['sort_order'] ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="shipping-method-cell">

                                        <div class="shipping-method-icon shipping-method-logo">

                                            <?php if (!empty($shippingMethod['logo'])) : ?>

                                                <img
                                                    src="../<?= htmlspecialchars($shippingMethod['logo']) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $shippingMethod['carrier']
                                                        ?: $shippingMethod['name']
                                                    ) ?>"
                                                >

                                            <?php else : ?>

                                                <i class="<?= htmlspecialchars($deliveryTypeIcon) ?>"></i>

                                            <?php endif; ?>

                                        </div>

                                        <div>
                                            <strong>
                                                <?= htmlspecialchars($shippingMethod['name']) ?>
                                            </strong>

                                            <?php if (!empty($shippingMethod['description'])) : ?>
                                                <span>
                                                    <?= htmlspecialchars($shippingMethod['description']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($shippingMethod['carrier'])) : ?>

                                        <?= htmlspecialchars($shippingMethod['carrier']) ?>

                                    <?php else : ?>

                                        <span class="admin-muted">
                                            Non renseigné
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="shipping-type-badge">
                                        <i class="<?= htmlspecialchars($deliveryTypeIcon) ?>"></i>

                                        <?= htmlspecialchars($deliveryTypeLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($price === 0.0) : ?>

                                        <span class="shipping-free-price">
                                            Gratuit
                                        </span>

                                    <?php else : ?>

                                        <strong>
                                            <?= number_format($price, 2, ',', ' ') ?> €
                                        </strong>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (
                                        $freeThreshold !== null
                                        && $freeThreshold !== ''
                                    ) : ?>

                                        <?= number_format(
                                            (float) $freeThreshold,
                                            2,
                                            ',',
                                            ' '
                                        ) ?> €

                                    <?php elseif ($price === 0.0) : ?>

                                        <span class="shipping-free-price">
                                            Toujours
                                        </span>

                                    <?php else : ?>

                                        <span class="admin-muted">
                                            Aucun seuil
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($shippingMethod['estimated_delay'])) : ?>

                                        <?= htmlspecialchars(
                                            $shippingMethod['estimated_delay']
                                        ) ?>

                                    <?php else : ?>

                                        <span class="admin-muted">
                                            Non renseigné
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($isActive) : ?>

                                        <span class="admin-status-badge status-paid">
                                            <i class="fa-solid fa-circle-check"></i>
                                            Actif
                                        </span>

                                    <?php else : ?>

                                        <span class="admin-status-badge status-cancelled">
                                            <i class="fa-solid fa-circle-pause"></i>
                                            Inactif
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <a
                                            href="shipping-edit.php?id=<?= (int) $shippingMethod['id'] ?>"
                                            class="table-action-link"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                            Modifier
                                        </a>

                                        <a
                                            href="shipping-delete.php?id=<?= (int) $shippingMethod['id'] ?>"
                                            class="danger-link table-action-link"
                                            onclick="return confirm(
                                                'Supprimer définitivement ce mode de livraison ?'
                                            )"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            Supprimer
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>