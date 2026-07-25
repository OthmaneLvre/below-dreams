<?php

require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$customersCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE payment_status = 'paid'
")->fetchColumn();

$todayRevenue = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE payment_status = 'paid'
    AND DATE(paid_at) = CURDATE()
")->fetchColumn();

$ordersToPrepare = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status IN ('paid', 'processing')
")->fetchColumn();

$lowStockCount = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'stock'
    AND stock <= 5
")->fetchColumn();

$latestOrders = $pdo->query("
    SELECT
        orders.id,
        orders.order_number,
        orders.total,
        orders.status,
        orders.created_at,
        customers.firstname,
        customers.lastname
    FROM orders
    INNER JOIN customers ON orders.customer_id = customers.id
    ORDER BY orders.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$bestProducts = $pdo->query("
    SELECT
        order_items.product_name,
        SUM(order_items.quantity) AS total_sold
    FROM order_items
    INNER JOIN orders ON order_items.order_id = orders.id
    WHERE orders.payment_status = 'paid'
    GROUP BY order_items.product_name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$lowStockProducts = $pdo->query("
    SELECT id, name, stock
    FROM products
    WHERE status = 'stock'
    AND stock <= 5
    ORDER BY stock ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Dashboard | Below Dreams";

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header">
        <div>
            <h1>Dashboard</h1>
            <p>Bienvenue <?= htmlspecialchars($_SESSION['admin_name']) ?></p>
        </div>
    </header>

    <section class="admin-section">
        <h2>Vue d’ensemble</h2>

        <div class="stats-grid dashboard-stats-grid">

            <article class="stat-card">
                <span>Chiffre d'affaires total</span>
                <strong><?= number_format($totalRevenue, 2, ',', ' ') ?> €</strong>
            </article>

            <article class="stat-card">
                <span>CA aujourd'hui</span>
                <strong><?= number_format($todayRevenue, 2, ',', ' ') ?> €</strong>
            </article>

            <article class="stat-card">
                <span>Commandes</span>
                <strong><?= $ordersCount ?></strong>
            </article>

            <article class="stat-card">
                <span>À préparer</span>
                <strong><?= $ordersToPrepare ?></strong>
            </article>

            <article class="stat-card">
                <span>Clients</span>
                <strong><?= $customersCount ?></strong>
            </article>

            <article class="stat-card">
                <span>Stock faible</span>
                <strong><?= $lowStockCount ?></strong>
            </article>

        </div>
    </section>

    <section class="admin-dashboard-grid">

        <div class="admin-section">
            <h2>Dernières commandes</h2>

            <?php if (empty($latestOrders)) : ?>
                <p class="admin-muted">Aucune commande récente.</p>
            <?php else : ?>

                <div class="dashboard-list">
                    <?php foreach ($latestOrders as $order) : ?>
                        <a href="order.php?id=<?= (int) $order['id'] ?>" class="dashboard-list-item">
                            <div>
                                <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                                <span><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></span>
                            </div>

                            <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h2>Produits les plus vendus</h2>

            <?php if (empty($bestProducts)) : ?>
                <p class="admin-muted">Aucune vente enregistrée.</p>
            <?php else : ?>

                <div class="dashboard-list">
                    <?php foreach ($bestProducts as $product) : ?>
                        <div class="dashboard-list-item">
                            <div>
                                <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                <span><?= (int) $product['total_sold'] ?> vente<?= (int) $product['total_sold'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h2>Stock faible</h2>

            <?php if (empty($lowStockProducts)) : ?>
                <p class="admin-muted">Aucun produit en stock faible.</p>
            <?php else : ?>

                <div class="dashboard-list">
                    <?php foreach ($lowStockProducts as $product) : ?>
                        <a href="product-edit.php?id=<?= (int) $product['id'] ?>" class="dashboard-list-item">
                            <div>
                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                <span>Stock restant : <?= (int) $product['stock'] ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>
