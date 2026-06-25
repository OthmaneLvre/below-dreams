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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Below Dreams</title>
</head>
<body>

    <h1>Dashboard Below Dreams</h1>
    <p>Bienvenue <?= htmlspecialchars($_SESSION['admin_name']) ?></p>

    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="products.php">Produits</a> |
        <a href="orders.php">Commandes</a> |
        <a href="customers.php">Clients</a> |
        <a href="logout.php">Déconnexion</a>
    </nav>

    <hr>

    <section>
        <h2>Vue d’ensemble</h2>

        <div>
            <article>
                <h3>Produits</h3>
                <p><?= $productsCount ?></p>
            </article>

            <article>
                <h3>Clients</h3>
                <p><?= $customersCount ?></p>
            </article>

            <article>
                <h3>Commandes</h3>
                <p><?= $ordersCount ?></p>
            </article>
        </div>
    </section>

</body>
</html>