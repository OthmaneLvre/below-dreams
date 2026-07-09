<?php
session_start();

require_once 'config/database.php';
require_once 'config/stripe.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    header('Location: shop.php');
    exit;
}

$stripeSession = \Stripe\Checkout\Session::retrieve($sessionId);

if (
    empty($stripeSession->client_reference_id) ||
    $stripeSession->payment_status !== 'paid'
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
$basePath = '';

require_once 'partials/header.php';
?>

<main>
    <section class="success-page">
        <div class="container success-inner">

            <div class="success-card">

                <div class="success-icon">✅</div>

                <h1>Commande confirmée</h1>

                <p>Merci pour votre commande.</p>

                <p>
                    Numéro de commande :
                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                </p>

                <p>
                    Total :
                    <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong>
                </p>

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