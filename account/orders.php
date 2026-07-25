<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Mes commandes | Below Dreams";
$pageRobots = 'noindex, nofollow';
$basePath = '../';

$query = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE customer_id = ?
    ORDER BY created_at DESC
");

$query->execute([$_SESSION['customer_id']]);

$orders = $query->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => 'En attente',
    'paid' => 'Payée',
    'processing' => 'En préparation',
    'shipped' => 'Expédiée',
    'completed' => 'Terminée',
    'cancelled' => 'Annulée'
];

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mes commandes</h1>

        <p>
            Consultez l'historique de vos commandes Below Dreams.
        </p>
    </header>

    <section class="account-section">

        <?php if (empty($orders)) : ?>

            <div class="account-empty">

                <div class="account-empty-icon">
                    📦
                </div>

                <h2>
                    Aucune commande
                </h2>

                <p>
                    Vous n'avez encore passé aucune commande.
                </p>

                <a href="../shop.php" class="btn-primary">
                    Découvrir la boutique
                </a>

            </div>

        <?php else : ?>

            <table class="orders-table">

                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($orders as $order) : ?>

                    <?php
                    $status = $statusLabels[$order['status']] ?? 'Inconnu';
                    ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($order['order_number']) ?>
                        </td>

                        <td>
                            <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($status) ?>
                        </td>

                        <td>
                            <?= number_format($order['total'], 2, ',', ' ') ?> €
                        </td>

                        <td>
                            <a
                                class="btn-primary btn-small"
                                href="order.php?id=<?= $order['id'] ?>"
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

</div>

<?php require_once '../partials/footer.php'; ?>
