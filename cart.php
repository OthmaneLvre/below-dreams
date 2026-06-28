<?php
$pageTitle = "Below Dreams | Panier";
$pageDescription = "Consultez votre panier Below Dreams avant de finaliser votre commande.";
$basePath = '';

require_once 'partials/header.php';
?>

    <main>
        <section class="cart-page">
            <div class="container cart-page-inner">

                <div class="cart-header">
                    <h1>Mon panier</h1>
                    <p>Vérifiez vos articles avant de passer commande.</p>
                </div>

                <div class="cart-layout">

                    <div class="cart-items" id="cart-items">
                        <p class="cart-empty">Votre panier est vide.</p>
                    </div>

                    <aside class="cart-summary">
                        <h2>Résumé</h2>

                        <div class="cart-summary-line">
                            <span>Sous-total</span>
                            <strong id="cart-subtotal">0,00 €</strong>
                        </div>

                        <div class="cart-summary-line">
                            <span>Livraison</span>
                            <strong>Calculée après</strong>
                        </div>

                        <div class="cart-summary-total">
                            <span>Total</span>
                            <strong id="cart-total">0,00 €</strong>
                        </div>

                        <a href="checkout.php" class="btn-primary cart-checkout" >
                            Commander
                        </a>
                    </aside>

                </div>

            </div>
        </section>
    </main>

<?php require_once 'partials/footer.php'; ?>