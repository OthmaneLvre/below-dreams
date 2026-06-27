<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$query = $pdo->query("
    SELECT *
    FROM products
    ORDER BY id DESC
");

$products = $query->fetchAll(PDO::FETCH_ASSOC);

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Gestion des produits</h1>
            <p>Ajoute, modifie et supprime les produits de la boutique.</p>
        </div>

        <a href="product-add.php" class="admin-btn">
            Ajouter un produit
        </a>
    </header>

    <section class="admin-section">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><?= $product['id'] ?></td>

                            <td>
                                <?php if (!empty($product['image'])) : ?>
                                    <img src="../<?= htmlspecialchars($product['image']) ?>" class="product-thumb" alt="">
                                <?php else : ?>
                                    <span class="empty-image">Aucune</span>
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td><?= number_format($product['price'], 2, ',', ' ') ?> €</td>

                            <td>
                                <span class="status-badge">
                                    <?= htmlspecialchars($product['status']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="product-edit.php?id=<?= $product['id'] ?>">Modifier</a>
                                    <a href="product-delete.php?id=<?= $product['id'] ?>" class="danger-link" onclick="return confirm('Supprimer ce produit ?')">
                                        Supprimer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?php require_once 'partials/footer.php'; ?>