<?php

require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Commandes | Administration Below Dreams";

require_once 'partials/header.php';
require_once 'partials/sidebar.php';

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

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT
        orders.id,
        orders.order_number,
        orders.total,
        orders.status,
        orders.created_at,
        customers.firstname,
        customers.lastname,
        customers.email
    FROM orders
    INNER JOIN customers
        ON orders.customer_id = customers.id
    WHERE 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            orders.order_number LIKE ?
            OR customers.firstname LIKE ?
            OR customers.lastname LIKE ?
            OR customers.email LIKE ?
        )
    ";

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== '') {

    $sql .= " AND orders.status = ?";

    $params[] = $statusFilter;
}

$sql .= " ORDER BY orders.created_at DESC";

$query = $pdo->prepare($sql);

$query->execute($params);

$orders = $query->fetchAll(PDO::FETCH_ASSOC);

$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, fn($order) => $order['status'] === 'pending'));
$paidOrders = count(array_filter($orders, fn($order) => $order['status'] === 'paid'));
?>

<main class="admin-main">

    <header class="admin-header">
        <div>
            <h1>Commandes</h1>
            <p>Consultez et gérez les commandes passées sur Below Dreams.</p>
        </div>
    </header>

    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <span>Total commandes</span>
            <strong><?= $totalOrders ?> commande<?= $totalOrders > 1 ? 's' : '' ?></strong>
        </div>

        <div class="admin-stat-card">
            <span>En attente</span>
            <strong><?= $pendingOrders ?> commande<?= $pendingOrders > 1 ? 's' : '' ?></strong>
        </div>

        <div class="admin-stat-card">
            <span>Payées</span>
            <strong><?= $paidOrders ?> commande<?= $paidOrders > 1 ? 's' : '' ?></strong>
        </div>

    </section>

    <section class="admin-filters">

        <form method="GET" class="admin-search-form">

            <input
                type="text"
                name="search"
                placeholder="Rechercher une commande, un client ou un email..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
            >

            <select name="status">

                <option value="">Tous les statuts</option>

                <?php foreach ($statusLabels as $key => $label) : ?>

                    <option
                        value="<?= $key ?>"
                        <?= (($_GET['status'] ?? '') === $key) ? 'selected' : '' ?>
                    >
                        <?= $label ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button class="admin-btn">
                Rechercher
            </button>

        </form>

    </section>

    <section class="admin-section">

        <?php if (empty($orders)) : ?>

            <div class="admin-empty">
                <h2>Aucune commande</h2>
                <p>Aucune commande ne correspond à votre recherche.</p>
            </div>

        <?php else : ?>

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($orders as $order) : ?>

                        <?php
                        $status = $statusLabels[$order['status']] ?? 'Inconnu';
                        $statusClass = $statusClasses[$order['status']] ?? 'status-unknown';
                        ?>

                        <tr onclick="window.location.href='order.php?id=<?= (int) $order['id'] ?>'">

                            <td>
                                <strong class="admin-order-number">
                                    #<?= htmlspecialchars($order['order_number']) ?>
                                </strong>
                            </td>

                            <td>
                                <div class="admin-customer-cell">
                                    <strong><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></strong>
                                    <span><?= htmlspecialchars($order['email']) ?></span>
                                </div>
                            </td>

                            <td>
                                <div class="admin-date-cell">
                                    <strong><?= date('d/m/Y', strtotime($order['created_at'])) ?></strong>
                                    <span><?= date('H:i', strtotime($order['created_at'])) ?></span>
                                </div>
                            </td>

                            <td>
                                <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong>
                            </td>

                            <td>
                                <span class="admin-status-badge <?= htmlspecialchars($statusClass) ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="order.php?id=<?= (int) $order['id'] ?>"
                                    class="admin-btn admin-btn-small"
                                    onclick="event.stopPropagation();"
                                >
                                    Voir le détail
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>