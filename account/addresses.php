<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Mes adresses | Below Dreams";
$basePath = '../';

$success = '';
$error = '';

$query = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$query->execute([$_SESSION['customer_id']]);
$customer = $query->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address']);
    $shippingPostcode = trim($_POST['shipping_postcode']);
    $shippingCity = trim($_POST['shipping_city']);
    $shippingCountry = trim($_POST['shipping_country']);

    $billingAddress = trim($_POST['billing_address']);
    $billingPostcode = trim($_POST['billing_postcode']);
    $billingCity = trim($_POST['billing_city']);
    $billingCountry = trim($_POST['billing_country']);

    if (!$shippingAddress || !$shippingPostcode || !$shippingCity || !$shippingCountry) {
        $error = "Merci de remplir l'adresse de livraison.";
    } else {
        $update = $pdo->prepare("
            UPDATE customers
            SET
                shipping_address = ?,
                shipping_postcode = ?,
                shipping_city = ?,
                shipping_country = ?,
                billing_address = ?,
                billing_postcode = ?,
                billing_city = ?,
                billing_country = ?
            WHERE id = ?
        ");

        $update->execute([
            $shippingAddress,
            $shippingPostcode,
            $shippingCity,
            $shippingCountry,
            $billingAddress,
            $billingPostcode,
            $billingCity,
            $billingCountry,
            $_SESSION['customer_id']
        ]);

        $success = "Vos adresses ont bien été mises à jour.";

        $query->execute([$_SESSION['customer_id']]);
        $customer = $query->fetch(PDO::FETCH_ASSOC);
    }
}

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mes adresses</h1>
        <p>Gérez vos adresses de livraison et de facturation.</p>
    </header>

    <section class="account-section">

        <?php if (!empty($success)) : ?>
            <p class="account-success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <p class="account-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="account-form account-form-large">

            <div class="address-grid">

                <div class="address-card">
                    <h2>Adresse de livraison</h2>

                    <label>Adresse *</label>
                    <textarea name="shipping_address" required><?= htmlspecialchars($customer['shipping_address'] ?? '') ?></textarea>

                    <label>Code postal *</label>
                    <input type="text" name="shipping_postcode" value="<?= htmlspecialchars($customer['shipping_postcode'] ?? '') ?>" required>

                    <label>Ville *</label>
                    <input type="text" name="shipping_city" value="<?= htmlspecialchars($customer['shipping_city'] ?? '') ?>" required>

                    <label>Pays *</label>
                    <input type="text" name="shipping_country" value="<?= htmlspecialchars($customer['shipping_country'] ?? 'France') ?>" required>
                </div>

                <label class="same-address-check">
                    <input type="checkbox" id="same-address">
                    Utiliser la même adresse pour la facturation
                </label>

                <div class="address-card">
                    <h2>Adresse de facturation</h2>

                    <label>Adresse</label>
                    <textarea name="billing_address"><?= htmlspecialchars($customer['billing_address'] ?? '') ?></textarea>

                    <label>Code postal</label>
                    <input type="text" name="billing_postcode" value="<?= htmlspecialchars($customer['billing_postcode'] ?? '') ?>">

                    <label>Ville</label>
                    <input type="text" name="billing_city" value="<?= htmlspecialchars($customer['billing_city'] ?? '') ?>">

                    <label>Pays</label>
                    <input type="text" name="billing_country" value="<?= htmlspecialchars($customer['billing_country'] ?? 'France') ?>">
                </div>

            </div>

            <button type="submit" class="btn-primary account-submit">
                Enregistrer les adresses
            </button>

        </form>

    </section>

</main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sameAddressCheckbox = document.getElementById('same-address');

    if (!sameAddressCheckbox) return;

    sameAddressCheckbox.addEventListener('change', () => {
        if (sameAddressCheckbox.checked) {
            document.querySelector('[name="billing_address"]').value =
                document.querySelector('[name="shipping_address"]').value;

            document.querySelector('[name="billing_postcode"]').value =
                document.querySelector('[name="shipping_postcode"]').value;

            document.querySelector('[name="billing_city"]').value =
                document.querySelector('[name="shipping_city"]').value;

            document.querySelector('[name="billing_country"]').value =
                document.querySelector('[name="shipping_country"]').value;
        }
    });
});
</script>

<?php require_once '../partials/footer.php'; ?>
