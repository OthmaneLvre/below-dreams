<?php

require_once 'config/database.php';

/*
|--------------------------------------------------------------------------
| Tarif de livraison minimum
|--------------------------------------------------------------------------
|
| On récupère le prix payant le moins élevé parmi les modes actifs.
| Le montant définitif sera calculé dans le checkout selon le mode choisi.
|
*/

$shippingPriceQuery = $pdo->query("
    SELECT MIN(price)
    FROM shipping_methods
    WHERE is_active = 1
    AND price > 0
");

$minimumShippingPrice = $shippingPriceQuery->fetchColumn();

$freeShippingQuery = $pdo->query("
    SELECT COUNT(*)
    FROM shipping_methods
    WHERE is_active = 1
    AND price = 0
");

$hasAlwaysFreeShipping = (int) $freeShippingQuery->fetchColumn() > 0;

$pageTitle = "Below Dreams | Panier";
$pageDescription = "Consultez votre panier Below Dreams avant de finaliser votre commande.";
$pageRobots = 'noindex, nofollow';
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

                <aside
                    class="cart-summary"
                    data-minimum-shipping-price="<?= $minimumShippingPrice !== false
                        && $minimumShippingPrice !== null
                            ? htmlspecialchars(
                                number_format(
                                    (float) $minimumShippingPrice,
                                    2,
                                    '.',
                                    ''
                                )
                            )
                            : '' ?>"
                    data-has-free-shipping="<?= $hasAlwaysFreeShipping ? '1' : '0' ?>"
                >
                    <h2>Résumé</h2>

                    <div class="cart-summary-line">
                        <span>Sous-total</span>
                        <strong id="cart-subtotal">0,00 €</strong>
                    </div>

                    <div class="cart-summary-line">
                        <span>Livraison</span>

                        <strong id="cart-shipping-estimate">
                            <?php if (
                                $minimumShippingPrice !== false
                                && $minimumShippingPrice !== null
                            ) : ?>

                                À partir de
                                <?= number_format(
                                    (float) $minimumShippingPrice,
                                    2,
                                    ',',
                                    ' '
                                ) ?> €

                            <?php elseif ($hasAlwaysFreeShipping) : ?>

                                Gratuite

                            <?php else : ?>

                                Indisponible

                            <?php endif; ?>
                        </strong>
                    </div>

                    <p class="cart-shipping-note">
                        Le tarif définitif dépendra du mode de livraison choisi.
                    </p>

                    <div class="cart-summary-total">
                        <span>Total hors livraison</span>
                        <strong id="cart-total">0,00 €</strong>
                    </div>

                    <a href="checkout.php" class="btn-primary cart-checkout">
                        Commander
                    </a>
                </aside>

            </div>

        </div>
    </section>
</main>

<?php require_once 'partials/footer.php'; ?>