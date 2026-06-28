<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Mon compte | Below Dreams";

$query = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
");

$query->execute([$_SESSION['customer_id']]);

$customer = $query->fetch(PDO::FETCH_ASSOC);

$basePath = '../';

require_once '../partials/header.php';
?>

<div class="account-area">
    <?php require_once 'partials/sidebar.php'; ?>

    <main class="account-main">

        <header class="account-header">
            <h1>Bonjour <?= htmlspecialchars($customer['firstname']) ?> 👋</h1>

            <p>
                Bienvenue dans votre espace client Below Dreams.
            </p>
        </header>

        <section class="account-section">

            <h2>Accès rapide</h2>

            <div class="account-grid">

                <article class="account-box">

                    <h2>📦 Mes commandes</h2>

                    <p>
                        Consultez l'historique et le suivi de vos commandes.
                    </p>

                    <br>

                    <a class="btn-primary" href="orders.php">
                        Voir mes commandes
                    </a>

                </article>

                <article class="account-box">

                    <h2>👤 Mon profil</h2>

                    <p>
                        Modifiez vos informations personnelles.
                    </p>

                    <br>

                    <a class="btn-primary" href="profile.php">
                        Modifier
                    </a>

                </article>

                <article class="account-box">

                    <h2>🏠 Mes adresses</h2>

                    <p>
                        Gérez vos adresses de livraison et de facturation.
                    </p>

                    <br>

                    <a class="btn-primary" href="addresses.php">
                        Gérer
                    </a>

                </article>

                <article class="account-box">

                    <h2>🔒 Mot de passe</h2>

                    <p>
                        Modifiez votre mot de passe.
                    </p>

                    <br>

                    <a class="btn-primary" href="password.php">
                        Modifier
                    </a>

                </article>

                <article class="account-box">

                    <h2>🚚 Dernière commande</h2>

                    <p>
                        Aucune commande pour le moment.
                    </p>

                </article>

                <article class="account-box">

                    <h2>❤️ Below Dreams</h2>

                    <p>
                        Merci de faire partie de la communauté.
                    </p>

                </article>

            </div>

        </section>

    </main>

</div>

<?php require_once '../partials/footer.php'; ?>