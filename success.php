<?php
session_start();

require_once 'config/database.php';
require_once 'config/stripe.php';

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
    LIMIT 1
");

$query->execute([
    $orderId,
    $_SESSION['customer_id']
]);

$order = $query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: shop.php');
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