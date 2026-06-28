<?php
session_start();

require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

$query = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
");

$query->execute([$_SESSION['customer_id']]);
$customer = $query->fetch(PDO::FETCH_ASSOC);

$pageTitle = "Checkout | Below Dreams";
$pageDescription = "Finalisez votre commande Below Dreams.";
$basePath = '';

require_once 'partials/header.php';
?>

<main>
    <section class="checkout-page">
        <div class="container checkout-inner">

            <header class="checkout-header">
                <h1>Finaliser ma commande</h1>
                <p>Vérifiez vos informations avant validation.</p>
            </header>

            <div class="checkout-layout">

                <section class="checkout-main">

                    <article class="checkout-card">

                        <div class="checkout-card-header">
                            <h2>Informations</h2>

                            <a href="account/profile.php" class="checkout-edit">
                                Modifier
                            </a>
                        </div>

                        <p>
                            <strong>
                                <?= htmlspecialchars($customer['firstname']) ?>
                                <?= htmlspecialchars($customer['lastname']) ?>
                            </strong>
                        </p>

                        <p><?= htmlspecialchars($customer['email']) ?></p>

                        <?php if (!empty($customer['phone'])) : ?>
                            <p><?= htmlspecialchars($customer['phone']) ?></p>
                        <?php endif; ?>

                    </article>

                    <article class="checkout-card">
                        <div class="checkout-card-header">

                            <h2>Adresse de livraison</h2>

                            <a href="account/addresses.php" class="checkout-edit">
                                Modifier
                            </a>

                        </div>

                        <?php if (!empty($customer['shipping_address'])) : ?>
                            <p><?= nl2br(htmlspecialchars($customer['shipping_address'])) ?></p>
                            <p>
                                <?= htmlspecialchars($customer['shipping_postcode']) ?>
                                <?= htmlspecialchars($customer['shipping_city']) ?>
                            </p>
                            <p><?= htmlspecialchars($customer['shipping_country']) ?></p>
                        <?php else : ?>
                            <p>Aucune adresse de livraison enregistrée.</p>
                            <a href="account/addresses.php" class="btn-primary">
                                Ajouter une adresse
                            </a>
                        <?php endif; ?>
                    </article>

                    <article class="checkout-card">
                        <div class="checkout-card-header">

                            <h2>Adresse de facturation</h2>

                            <a href="account/addresses.php" class="checkout-edit">
                                Modifier
                            </a>

                        </div>

                        <?php if (!empty($customer['billing_address'])) : ?>
                            <p><?= nl2br(htmlspecialchars($customer['billing_address'])) ?></p>
                            <p>
                                <?= htmlspecialchars($customer['billing_postcode']) ?>
                                <?= htmlspecialchars($customer['billing_city']) ?>
                            </p>
                            <p><?= htmlspecialchars($customer['billing_country']) ?></p>
                        <?php else : ?>
                            <p>Adresse identique ou non renseignée.</p>
                        <?php endif; ?>
                    </article>

                    <article class="checkout-card">
                        <h2>Articles</h2>
                        <div id="checkout-items"></div>
                    </article>

                </section>

                <aside class="checkout-summary">
                    <h2>Résumé</h2>

                    <div class="checkout-summary-line">
                        <span>Sous-total</span>
                        <strong id="checkout-subtotal">0,00 €</strong>
                    </div>

                    <div class="checkout-summary-line">
                        <span>Livraison</span>
                        <strong>Calculée après</strong>
                    </div>

                    <div class="checkout-summary-total">
                        <span>Total</span>
                        <strong id="checkout-total">0,00 €</strong>
                    </div>

                    <button class="btn-primary checkout-submit" type="button">
                        Continuer vers le paiement
                    </button>
                </aside>

            </div>

        </div>
    </section>
</main>

<script src="js/checkout.js" defer></script>

<?php require_once 'partials/footer.php'; ?>