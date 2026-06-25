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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Produits | Below Dreams</title>
</head>
<body>

<h1>Gestion des produits</h1>

<p>
    <a href="dashboard.php">Dashboard</a>
    |
    <a href="product-add.php">Ajouter un produit</a>
    |
    <a href="logout.php">Déconnexion</a>
</p>

<table border="1" cellpadding="10">

    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Catégorie</th>
        <th>Prix</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($products as $product) : ?>

        <tr>

            <td><?= $product['id'] ?></td>

            <td>
                <?= htmlspecialchars($product['name']) ?>
            </td>

            <td>
                <?= htmlspecialchars($product['category']) ?>
            </td>

            <td>
                <?= number_format($product['price'], 2, ',', ' ') ?> €
            </td>

            <td>
                <?= htmlspecialchars($product['status']) ?>
            </td>

            <td>

                <a href="product-edit.php?id=<?= $product['id'] ?>">
                    Modifier
                </a>

                |

                <a
                    href="product-delete.php?id=<?= $product['id'] ?>"
                    onclick="return confirm('Supprimer ce produit ?')"
                >
                    Supprimer
                </a>

            </td>

        </tr>

    <?php endforeach; ?>

</table>

</body>
</html>