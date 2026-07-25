<?php

require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['id'] ?? null;

if (!$orderId || !ctype_digit($orderId)) {
    header('Location: orders.php');
    exit;
}

$allowedStatuses = [
    'pending',
    'paid',
    'processing',
    'shipped',
    'completed',
    'cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');
    $carrier = trim($_POST['carrier'] ?? '');
    $trackingNumber = trim($_POST['tracking_number'] ?? '');

    if (in_array($newStatus, $allowedStatuses, true)) {
    $updateQuery = $pdo->prepare("
        UPDATE orders
        SET
            status = ?,
            admin_note = ?,
            carrier = ?,
            tracking_number = ?,
            shipped_at = CASE
                WHEN ? = 'shipped' AND shipped_at IS NULL THEN NOW()
                ELSE shipped_at
            END,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateQuery->execute([
        $newStatus,
        $adminNote,
        $carrier ?: null,
        $trackingNumber ?: null,
        $newStatus,
        $orderId
    ]);

        header('Location: order.php?id=' . $orderId . '&updated=1');
        exit;
    }
}

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

$orderQuery = $pdo->prepare("
    SELECT
        orders.*,
        customers.firstname,
        customers.lastname,
        customers.email,
        customers.phone,
        customers.billing_address,
        customers.billing_postcode,
        customers.billing_city,
        customers.billing_country,
        customers.shipping_address,
        customers.shipping_postcode,
        customers.shipping_city,
        customers.shipping_country
    FROM orders
    INNER JOIN customers ON orders.customer_id = customers.id
    WHERE orders.id = ?
    LIMIT 1
");

$orderQuery->execute([$orderId]);
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

$status = $statusLabels[$order['status']] ?? 'Inconnu';
$statusClass = $statusClasses[$order['status']] ?? 'status-unknown';

$pageTitle = "Commande " . $order['order_number'] . " | Administration Below Dreams";

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

<?php if (isset($_GET['updated'])) : ?>
    <div class="admin-success">
        La commande a bien été mise à jour.
    </div>
<?php endif; ?>

    <header class="admin-header admin-order-header">
        <div>
            <a href="orders.php" class="admin-back-link">← Retour aux commandes</a>

            <h1>Commande #<?= htmlspecialchars($order['order_number']) ?></h1>

            <p>
                Passée le <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?>
            </p>
        </div>

        <div class="admin-order-header-total">
            <span>Total</span>
            <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong>
        </div>
    </header>

    <div class="admin-order-layout">

        <section class="admin-order-content">

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Informations générales</h2>

                    <span class="admin-status-badge <?= htmlspecialchars($statusClass) ?>">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </div>

                <div class="admin-info-grid">
                    <div>
                        <span>Référence</span>
                        <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                    </div>

                    <div>
                        <span>Date</span>
                        <strong><?= date('d/m/Y', strtotime($order['created_at'])) ?></strong>
                    </div>

                    <div>
                        <span>Heure</span>
                        <strong><?= date('H:i', strtotime($order['created_at'])) ?></strong>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Client</h2>

                <div class="admin-customer-detail">
                    <strong><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></strong>
                    <span><?= htmlspecialchars($order['email']) ?></span>

                    <?php if (!empty($order['phone'])) : ?>
                        <span><?= htmlspecialchars($order['phone']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-address-grid">

                <div class="admin-card">
                    <h2>Adresse de livraison</h2>

                    <p>
                        <?= htmlspecialchars($order['shipping_address'] ?? '') ?><br>
                        <?= htmlspecialchars($order['shipping_postcode'] ?? '') ?>
                        <?= htmlspecialchars($order['shipping_city'] ?? '') ?><br>
                        <?= htmlspecialchars($order['shipping_country'] ?? '') ?>
                    </p>
                </div>

                <div class="admin-card">
                    <h2>Adresse de facturation</h2>

                    <p>
                        <?= htmlspecialchars($order['billing_address'] ?? '') ?><br>
                        <?= htmlspecialchars($order['billing_postcode'] ?? '') ?>
                        <?= htmlspecialchars($order['billing_city'] ?? '') ?><br>
                        <?= htmlspecialchars($order['billing_country'] ?? '') ?>
                    </p>
                </div>

            </div>

            <div class="admin-card">
                <h2>Articles commandés</h2>

                <div class="admin-order-items">

                    <?php foreach ($items as $item) : ?>

                        <?php
                        $lineTotal = (float) $item['price'] * (int) $item['quantity'];
                        ?>

                        <div class="admin-order-item">

                            <div class="admin-order-item-image">
                                <?php if (!empty($item['product_image'])) : ?>
                                    <img
                                        src="../<?= htmlspecialchars($item['product_image']) ?>"
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    >
                                <?php else : ?>
                                    <div class="admin-order-item-placeholder">
                                        Image indisponible
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="admin-order-item-info">
                                <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                <p>Taille : <?= htmlspecialchars($item['size']) ?></p>
                                <p>Quantité : <?= (int) $item['quantity'] ?></p>
                            </div>

                            <div class="admin-order-item-price">
                                <span>
                                    <?= number_format($item['price'], 2, ',', ' ') ?> €
                                    × <?= (int) $item['quantity'] ?>
                                </span>

                                <strong>
                                    <?= number_format($lineTotal, 2, ',', ' ') ?> €
                                </strong>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="admin-order-price-summary">

                    <div>
                        <span>Sous-total articles</span>

                        <strong>
                            <?= number_format(
                                (float) $order['subtotal'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>
                    </div>

                    <div>
                        <span>Frais de livraison</span>

                        <strong>
                            <?php if ((float) $order['shipping_price'] === 0.0) : ?>
                                Gratuit
                            <?php else : ?>
                                <?= number_format(
                                    (float) $order['shipping_price'],
                                    2,
                                    ',',
                                    ' '
                                ) ?> €
                            <?php endif; ?>
                        </strong>
                    </div>

                    <div class="admin-order-price-total">
                        <span>Total de la commande</span>

                        <strong>
                            <?= number_format(
                                (float) $order['total'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>
                    </div>

                </div>

            </div>

            <div class="admin-card">
                
                <h2>Livraison choisie</h2>

                <div class="admin-info-grid">

                    <div>
                        <span>Mode de livraison</span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['shippin_method_name']
                                ?? 'Non renseigné'
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Transporteur prévu</span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['shipping_carrier']
                                ?? 'Non renseigné'
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Type</span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['shipping_type']
                                ?? 'Non renseigné'
                            ) ?>
                        </strong>
                    </div>
                    
                </div>

            </div>

        </section>

        <aside class="admin-order-sidebar">

            <form method="POST" class="admin-card admin-order-form">

                <h2>Gestion</h2>

                <label for="status">Statut de la commande</label>

                <select name="status" id="status">
                    <?php foreach ($statusLabels as $key => $label) : ?>
                        <option
                            value="<?= htmlspecialchars($key) ?>"
                            <?= $order['status'] === $key ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="admin_note">Note interne</label>

                <textarea
                    name="admin_note"
                    id="admin_note"
                    rows="6"
                    placeholder="Ajouter une note interne..."
                ><?= htmlspecialchars($order['admin_note'] ?? '') ?></textarea>

                <label for="carrier">Transporteur</label>

                <input
                    type="text"
                    name="carrier"
                    id="carrier"
                    placeholder="Ex : Colissimo, Mondial Relay..."
                    value="<?= htmlspecialchars($order['carrier'] ?? '') ?>"
                >

                <label for="tracking_number">Numéro de suivi</label>

                <input
                    type="text"
                    name="tracking_number"
                    id="tracking_number"
                    placeholder="Ex : 8A12345678901"
                    value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                >

                <button type="submit" class="admin-btn">
                    Enregistrer
                </button>

            </form>

        </aside>

    </div>

</main>

<?php require_once 'partials/footer.php'; ?>