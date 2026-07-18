<?php
session_start();

require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

$customerQuery = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$customerQuery->execute([$_SESSION['customer_id']]);
$customer = $customerQuery->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header('Location: account/login.php');
    exit;
}

$shippingQuery = $pdo->query("
    SELECT
        id,
        name,
        carrier,
        logo,
        description,
        delivery_type,
        price,
        free_shipping_threshold,
        estimated_delay
    FROM shipping_methods
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
");

$shippingMethods = $shippingQuery->fetchAll(PDO::FETCH_ASSOC);

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

            <?php if (!empty($_SESSION['checkout_error'])) : ?>
                <div class="checkout-alert">
                    <?= htmlspecialchars($_SESSION['checkout_error']) ?>
                </div>

                <?php unset($_SESSION['checkout_error']); ?>
            <?php endif; ?>

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

                            <p>
                                <?= nl2br(
                                    htmlspecialchars($customer['shipping_address'])
                                ) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars($customer['shipping_postcode']) ?>
                                <?= htmlspecialchars($customer['shipping_city']) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars($customer['shipping_country']) ?>
                            </p>

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

                            <p>
                                <?= nl2br(
                                    htmlspecialchars($customer['billing_address'])
                                ) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars($customer['billing_postcode']) ?>
                                <?= htmlspecialchars($customer['billing_city']) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars($customer['billing_country']) ?>
                            </p>

                        <?php else : ?>

                            <p>Adresse identique ou non renseignée.</p>

                        <?php endif; ?>

                    </article>

                    <article class="checkout-card">

                        <div class="checkout-card-header">
                            <div>
                                <h2>Mode de livraison</h2>
                                <p>Choisissez votre mode de livraison.</p>
                            </div>
                        </div>

                        <?php if (empty($shippingMethods)) : ?>

                            <div class="checkout-shipping-empty">
                                Aucun mode de livraison n’est disponible actuellement.
                            </div>

                        <?php else : ?>

                            <div class="checkout-shipping-list">

                                <?php foreach ($shippingMethods as $index => $method) : ?>

                                    <?php
                                    $price = (float) $method['price'];

                                    $threshold = $method['free_shipping_threshold'];

                                    $thresholdValue = (
                                        $threshold !== null
                                        && $threshold !== ''
                                    )
                                        ? (float) $threshold
                                        : null;
                                    ?>

                                    <label class="checkout-shipping-option">

                                        <input
                                            type="radio"
                                            name="shipping_method_display"
                                            value="<?= (int) $method['id'] ?>"
                                            data-shipping-price="<?= htmlspecialchars(
                                                number_format($price, 2, '.', '')
                                            ) ?>"
                                            data-free-threshold="<?= $thresholdValue !== null
                                                ? htmlspecialchars(
                                                    number_format(
                                                        $thresholdValue,
                                                        2,
                                                        '.',
                                                        ''
                                                    )
                                                )
                                                : '' ?>"
                                            <?= $index === 0 ? 'checked' : '' ?>
                                        >

                                        <span class="checkout-shipping-radio"></span>

                                        <?php if (!empty($method['logo'])) : ?>
                                            
                                            <span class="checkout-shipping-logo">
                                                <img
                                                    src="<?=  htmlspecialchars($method['logo']) ?>"
                                                    alt="<?=  htmlspecialchars(
                                                        $method['carrier']
                                                        ?: $method['name']
                                                    ) ?>"
                                                >
                                            </span>

                                        <?php else : ?>
                                            <span class="checkout-shipping-logo checkout-shipping-logo-placeholder">
                                                🚚
                                            </span>

                                        <?php endif; ?>

                                        <span class="checkout-shipping-content">

                                            <span class="checkout-shipping-header">

                                                <strong>
                                                    <?= htmlspecialchars($method['name']) ?>
                                                </strong>

                                                <span class="checkout-shipping-price">
                                                    <?php if ($price === 0.0) : ?>
                                                        Gratuit
                                                    <?php else : ?>
                                                        <?= number_format(
                                                            $price,
                                                            2,
                                                            ',',
                                                            ' '
                                                        ) ?> €
                                                    <?php endif; ?>
                                                </span>

                                            </span>

                                            <?php if (!empty($method['carrier'])) : ?>
                                                <span class="checkout-shipping-carrier">
                                                    <?= htmlspecialchars($method['carrier']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($method['description'])) : ?>
                                                <span class="checkout-shipping-description">
                                                    <?= htmlspecialchars(
                                                        $method['description']
                                                    ) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($method['estimated_delay'])) : ?>
                                                <span class="checkout-shipping-delay">
                                                    <?= htmlspecialchars(
                                                        $method['estimated_delay']
                                                    ) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($thresholdValue !== null) : ?>
                                                <span class="checkout-shipping-threshold">
                                                    Offerte dès
                                                    <?= number_format(
                                                        $thresholdValue,
                                                        2,
                                                        ',',
                                                        ' '
                                                    ) ?> €
                                                </span>
                                            <?php endif; ?>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

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
                        <strong id="checkout-shipping-price">
                            À sélectionner
                        </strong>
                    </div>

                    <div
                        class="checkout-shipping-saving"
                        id="checkout-shipping-saving"
                        hidden
                    ></div>

                    <div class="checkout-summary-total">
                        <span>Total</span>
                        <strong id="checkout-total">0,00 €</strong>
                    </div>

                    <form
                        method="POST"
                        action="checkout_process.php"
                        id="checkout-form"
                        class="checkout-form"
                    >
                        <input
                            type="hidden"
                            name="cart"
                            id="checkout-cart-input"
                        >

                        <input
                            type="hidden"
                            name="shipping_method_id"
                            id="checkout-shipping-method-input"
                        >

                        <div
                            class="checkout-legal-error"
                            id="checkout-legal-error"
                            role="alert"
                            aria-live="polite"
                            hidden
                        >
                            Vous devez accepter les conditions obligatoires avant de continuer.
                        </div>

                        <div class="checkout-legal">

                            <label class="checkout-legal-option">
                                <input
                                    type="checkbox"
                                    name="accept_cgv"
                                    id="accept-cgv"
                                    value="1"
                                    required
                                >

                                <span>
                                    J’accepte les
                                    <a
                                        href="cgv.php"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Conditions Générales de Vente
                                    </a>.
                                </span>
                            </label>

                            <label class="checkout-legal-option">
                                <input
                                    type="checkbox"
                                    name="accept_privacy"
                                    id="accept-privacy"
                                    value="1"
                                    required
                                >

                                <span>
                                    J’ai pris connaissance de la
                                    <a
                                        href="politique-confidentialite.php"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        politique de confidentialité
                                    </a>.
                                </span>
                            </label>

                            <label class="checkout-legal-option">
                                <input
                                    type="checkbox"
                                    name="accept_payment_obligation"
                                    id="accept-payment-obligation"
                                    value="1"
                                    required
                                >

                                <span>
                                    Je reconnais que cette commande implique une
                                    <strong>obligation de paiement</strong>.
                                </span>
                            </label>

                        </div>

                        <button
                            class="btn-primary checkout-submit"
                            id="checkout-submit"
                            type="submit"
                            disabled
                        >
                            Commander et payer
                        </button>

                        <p class="checkout-payment-note">
                            Paiement sécurisé par Stripe.
                        </p>
                    </form>

                </aside>

            </div>

        </div>
    </section>
</main>

<script src="js/checkout.js" defer></script>

<?php require_once 'partials/footer.php'; ?>