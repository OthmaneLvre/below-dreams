<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/security-headers.php';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/stripe.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

$sessionId = trim($_GET['session_id'] ?? '');

if ($sessionId === '') {
    header('Location: shop.php');
    exit;
}

try {
    $stripeSession = \Stripe\Checkout\Session::retrieve($sessionId);
} catch (Throwable $e) {
    header('Location: shop.php');
    exit;
}

if (
    empty($stripeSession->client_reference_id)
    || $stripeSession->payment_status !== 'paid'
) {
    header('Location: shop.php');
    exit;
}

$orderId = (int) $stripeSession->client_reference_id;

$query = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
      AND customer_id = ?
      AND stripe_session_id = ?
    LIMIT 1
");

$query->execute([
    $orderId,
    (int) $_SESSION['customer_id'],
    $sessionId
]);

$order = $query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: shop.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Vérification du montant et de la devise Stripe
|--------------------------------------------------------------------------
*/

$expectedAmount = (int) round(
    (float) $order['total'] * 100
);

$stripeAmount = $stripeSession->amount_total ?? null;
$stripeCurrency = $stripeSession->currency ?? null;

$stripeOrderId = filter_var(
    $stripeSession->metadata->order_id ?? null,
    FILTER_VALIDATE_INT
);

if (
    !is_int($stripeAmount)
    || $stripeAmount !== $expectedAmount
    || $stripeCurrency !== 'eur'
    || $stripeOrderId !== $orderId
) {
    error_log(
        '[SUCCESS] Stripe verification failed for order '
        . $orderId
    );

    http_response_code(403);
    exit('Impossible de vérifier cette commande.');
}

/*
|--------------------------------------------------------------------------
| Vérification de la confirmation définitive en base
|--------------------------------------------------------------------------
|
| Le webhook Stripe est responsable de la validation du paiement
| et de la confirmation du stock.
|
| Cette page ne doit jamais modifier le statut d'une commande.
|
*/

$isOrderConfirmed = (
    $order['payment_status'] === 'paid'
    && $order['stock_reservation_status'] === 'confirmed'
);

if (!$isOrderConfirmed) {

    http_response_code(202);

    header('Retry-After: 5');

    $pageTitle = 'Confirmation en cours | Below Dreams';
    $pageDescription = 'Votre commande est en cours de confirmation.';
    $pageRobots = 'noindex, nofollow';
    $basePath = '';

    require_once __DIR__ . '/partials/header.php';
    ?>

    <main>
        <section class="success-page">
            <div class="container success-inner">

                <div class="success-card">

                    <div class="success-icon">⏳</div>

                    <h1>Confirmation en cours</h1>

                    <p>
                        Votre paiement a été accepté.
                        Nous finalisons actuellement
                        la confirmation de votre commande.
                    </p>

                    <div class="success-reference">
                        <span>Numéro de commande</span>

                        <strong>
                            <?= htmlspecialchars(
                                (string) $order['order_number'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </div>

                    <p>
                        Cette opération prend généralement
                        quelques instants.
                    </p>

                    <div class="success-actions">

                        <a
                            href="<?= htmlspecialchars(
                                $_SERVER['REQUEST_URI'] ?? 'shop.php',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="btn-primary"
                        >
                            Vérifier à nouveau
                        </a>

                        <a href="account/orders.php" class="btn-secondary">
                            Voir mes commandes
                        </a>

                    </div>

                </div>

            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = "Commande confirmée | Below Dreams";
$pageDescription = "Votre commande Below Dreams a bien été enregistrée.";
$pageRobots = 'noindex, nofollow';
$basePath = '';

require_once 'partials/header.php';
?>

<main>
    <section class="success-page">
        <div class="container success-inner">

            <div class="success-card">

                <div class="success-icon">✓</div>

                <h1>Commande confirmée</h1>

                <p>
                    Merci pour votre commande. Votre paiement a bien été accepté.
                </p>

                <div class="success-reference">
                    <span>Numéro de commande</span>

                    <strong>
                        <?= htmlspecialchars($order['order_number']) ?>
                    </strong>
                </div>

                <div class="success-summary">

                    <div class="success-summary-line">
                        <span>Sous-total</span>

                        <strong>
                            <?= number_format(
                                (float) $order['subtotal'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>
                    </div>

                    <div class="success-summary-line">
                        <span>Livraison</span>

                        <strong>
                            <?php if ((float) $order['shipping_price'] === 0.0) : ?>
                                Gratuite
                            <?php else : ?>
                                <?= number_format(
                                    (float) $order['shipping_price'],
                                    2,
                                    ',',
                                    ' '
                                ) ?> €
                            <?php endif; ?>
                        </strong>
                    </div>

                    <div class="success-summary-total">
                        <span>Total payé</span>

                        <strong>
                            <?= number_format(
                                (float) $order['total'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>
                    </div>

                </div>

                <?php if (!empty($order['shipping_method_name'])) : ?>

                    <div class="success-shipping">

                        <span class="success-shipping-label">
                            Mode de livraison
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['shipping_method_name']
                            ) ?>
                        </strong>

                        <?php if (!empty($order['shipping_carrier'])) : ?>
                            <p>
                                <?= htmlspecialchars(
                                    $order['shipping_carrier']
                                ) ?>
                            </p>
                        <?php endif; ?>

                    </div>

                <?php endif; ?>

                <div class="success-actions">

                    <a href="account/orders.php" class="btn-primary">
                        Voir mes commandes
                    </a>

                    <a href="shop.php" class="btn-secondary">
                        Retour à la boutique
                    </a>

                </div>

            </div>

        </div>
    </section>
</main>

<script>
    localStorage.removeItem("belowdreams_cart");
</script>

<?php require_once 'partials/footer.php'; ?>