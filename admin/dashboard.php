<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$customersCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

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

        <div class="stats-grid">
            <article class="stat-card">
                <span>Produits</span>
                <strong><?= $productsCount ?></strong>
            </article>

            <article class="stat-card">
                <span>Clients</span>
                <strong><?= $customersCount ?></strong>
            </article>

            <article class="stat-card">
                <span>Commandes</span>
                <strong><?= $ordersCount ?></strong>
            </article>
        </div>
    </section>

</main>

<?php require_once 'partials/footer.php'; ?>
