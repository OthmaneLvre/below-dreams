<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Détail de commande | Below Dreams";
$basePath = '../';

$orderId = $_GET['id'] ?? null;

if (!$orderId || !ctype_digit($orderId)) {
    header('Location: orders.php');
    exit;
}

$orderQuery = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    AND customer_id = ?
    LIMIT 1
");

$orderQuery->execute([
    $orderId,
    $_SESSION['customer_id']
]);

$order = $orderQuery->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

$itemsQuery = $pdo->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
");

$itemsQuery->execute([$order['id']]);

$items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => 'En attente',
    'paid' => 'Payée',
    'processing' => 'En préparation',
    'shipped' => 'Expédiée',
    'completed' => 'Terminée',
    'cancelled' => 'Annulée'
];

$statusClasses = [
    'pending' => 'status-pending',
    'paid' => 'status-paid',
    'processing' => 'status-processing',
    'shipped' => 'status-shipped',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled'
];

$status = $statusLabels[$order['status']] ?? 'Inconnu';
$statusClass = $statusClasses[$order['status']] ?? 'status-unknown';

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">

        <h1>Commande <?= htmlspecialchars($order['order_number']) ?></h1>

        <div class="order-reference">
            <span>Référence</span>
            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
        </div>

        <p>
            Retrouvez le détail de votre commande Below Dreams.
        </p>

    </header>

    <section class="account-section">

        <div class="order-detail-header">

            <div>
                <strong>Date de commande</strong>
                <span><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></span>
            </div>

            <div>
                <strong>Statut</strong>
                <span class="order-status-badge <?= htmlspecialchars($statusClass) ?>">
                    <?= htmlspecialchars($status) ?>
                </span>
            </div>

            <div>
                <strong>Total</strong>
                <span><?= number_format($order['total'], 2, ',', ' ') ?> €</span>
            </div>

        </div>

        <h2 class="account-section-title">Articles commandés</h2>

        <div class="order-items">

            <?php foreach ($items as $item) : ?>

                <?php
                $lineTotal = (float) $item['price'] * (int) $item['quantity'];
                ?>

                <div class="order-item">

                    <div class="order-item-image">
                        <?php if (!empty($item['product_image'])) : ?>
                            <img
                                src="../<?= htmlspecialchars($item['product_image']) ?>"
                                alt="<?= htmlspecialchars($item['product_name']) ?>"
                            >
                        <?php else : ?>
                            <div class="order-item-placeholder">
                                Image indisponible
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-item-info">
                        <h3><?= htmlspecialchars($item['product_name']) ?></h3>

                        <p>
                            Taille : <?= htmlspecialchars($item['size']) ?>
                        </p>

                        <p>
                            Quantité : <?= (int) $item['quantity'] ?>
                        </p>
                    </div>

                    <div class="order-item-price">
                        <span>
                            <?= number_format($item['price'], 2, ',', ' ') ?> € × <?= (int) $item['quantity'] ?>
                        </span>

                        <strong>
                            <?= number_format($lineTotal, 2, ',', ' ') ?> €
                        </strong>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="order-total">
            <span>Total de la commande</span>
            <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong>
        </div>

        <?php if (!empty($order['carrier']) || !empty($order['tracking_number']) || !empty($order['shipped_at'])) : ?>

            <div class="account-section-title">
                Livraison
            </div>

            <div class="order-detail-header">

                <div>
                    <strong>Transporteur</strong>
                    <span><?= htmlspecialchars($order['carrier'] ?? 'Non renseigné') ?></span>
                </div>

                <div>
                    <strong>Numéro de suivi</strong>
                    <span><?= htmlspecialchars($order['tracking_number'] ?? 'Non renseigné') ?></span>
                </div>

                <div>
                    <strong>Expédiée le</strong>
                    <span>
                        <?= !empty($order['shipped_at'])
                            ? date('d/m/Y à H:i', strtotime($order['shipped_at']))
                            : 'Non expédiée'
                        ?>
                    </span>
                </div>

            </div>

        <?php endif; ?>

        <a href="orders.php" class="btn-secondary">
            ← Retour à mes commandes
        </a>

    </section>

</main>

</div>

<?php require_once '../partials/footer.php'; ?>