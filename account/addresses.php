<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = "Mes adresses | Below Dreams";
$pageRobots = 'noindex, nofollow';
$basePath = '../';

$success = '';
$error = '';

$query = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$query->execute([
    (int) $_SESSION['customer_id']
]);

$customer = $query->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $shippingAddress = trim(
        $_POST['shipping_address'] ?? ''
    );

    $shippingPostcode = trim(
        $_POST['shipping_postcode'] ?? ''
    );

    $shippingCity = trim(
        $_POST['shipping_city'] ?? ''
    );

    $shippingCountry = trim(
        $_POST['shipping_country'] ?? ''
    );

    $billingAddress = trim(
        $_POST['billing_address'] ?? ''
    );

    $billingPostcode = trim(
        $_POST['billing_postcode'] ?? ''
    );

    $billingCity = trim(
        $_POST['billing_city'] ?? ''
    );

    $billingCountry = trim(
        $_POST['billing_country'] ?? ''
    );

    if (
        $shippingAddress === ''
        || $shippingPostcode === ''
        || $shippingCity === ''
        || $shippingCountry === ''
    ) {
        $error =
            "Merci de remplir l'adresse de livraison.";
    } elseif (mb_strlen($shippingAddress) > 500) {
        $error =
            "L'adresse de livraison est trop longue.";
    } elseif (mb_strlen($shippingPostcode) > 20) {
        $error =
            "Le code postal de livraison est trop long.";
    } elseif (mb_strlen($shippingCity) > 100) {
        $error =
            "La ville de livraison est trop longue.";
    } elseif (mb_strlen($shippingCountry) > 100) {
        $error =
            "Le pays de livraison est trop long.";
    } elseif (mb_strlen($billingAddress) > 500) {
        $error =
            "L'adresse de facturation est trop longue.";
    } elseif (mb_strlen($billingPostcode) > 20) {
        $error =
            "Le code postal de facturation est trop long.";
    } elseif (mb_strlen($billingCity) > 100) {
        $error =
            "La ville de facturation est trop longue.";
    } elseif (mb_strlen($billingCountry) > 100) {
        $error =
            "Le pays de facturation est trop long.";
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
            $billingAddress !== ''
                ? $billingAddress
                : null,
            $billingPostcode !== ''
                ? $billingPostcode
                : null,
            $billingCity !== ''
                ? $billingCity
                : null,
            $billingCountry !== ''
                ? $billingCountry
                : null,
            (int) $_SESSION['customer_id']
        ]);

        $success =
            "Vos adresses ont bien été mises à jour.";

        $query->execute([
            (int) $_SESSION['customer_id']
        ]);

        $customer = $query->fetch(PDO::FETCH_ASSOC);
    }
}

require_once __DIR__ . '/../partials/header.php';
?>

<div class="account-area">

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mes adresses</h1>

        <p>
            Gérez vos adresses de livraison et de facturation.
        </p>
    </header>

    <section class="account-section">

        <?php if ($success !== '') : ?>
            <p class="account-success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== '') : ?>
            <p class="account-error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <form
            method="POST"
            class="account-form account-form-large"
        >

            <?= csrfField() ?>

            <div class="address-grid">

                <div class="address-card">

                    <h2>Adresse de livraison</h2>

                    <label for="shipping_address">
                        Adresse *
                    </label>

                    <textarea
                        id="shipping_address"
                        name="shipping_address"
                        maxlength="500"
                        autocomplete="street-address"
                        required
                    ><?= htmlspecialchars(
                        $customer['shipping_address'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <label for="shipping_postcode">
                        Code postal *
                    </label>

                    <input
                        type="text"
                        id="shipping_postcode"
                        name="shipping_postcode"
                        value="<?= htmlspecialchars(
                            $customer['shipping_postcode'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="20"
                        autocomplete="postal-code"
                        required
                    >

                    <label for="shipping_city">
                        Ville *
                    </label>

                    <input
                        type="text"
                        id="shipping_city"
                        name="shipping_city"
                        value="<?= htmlspecialchars(
                            $customer['shipping_city'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                        autocomplete="address-level2"
                        required
                    >

                    <label for="shipping_country">
                        Pays *
                    </label>

                    <input
                        type="text"
                        id="shipping_country"
                        name="shipping_country"
                        value="<?= htmlspecialchars(
                            $customer['shipping_country']
                                ?? 'France',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                        autocomplete="country-name"
                        required
                    >

                </div>

                <label class="same-address-check">
                    <input
                        type="checkbox"
                        id="same-address"
                    >

                    Utiliser la même adresse pour la facturation
                </label>

                <div class="address-card">

                    <h2>Adresse de facturation</h2>

                    <label for="billing_address">
                        Adresse
                    </label>

                    <textarea
                        id="billing_address"
                        name="billing_address"
                        maxlength="500"
                    ><?= htmlspecialchars(
                        $customer['billing_address'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <label for="billing_postcode">
                        Code postal
                    </label>

                    <input
                        type="text"
                        id="billing_postcode"
                        name="billing_postcode"
                        value="<?= htmlspecialchars(
                            $customer['billing_postcode'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="20"
                    >

                    <label for="billing_city">
                        Ville
                    </label>

                    <input
                        type="text"
                        id="billing_city"
                        name="billing_city"
                        value="<?= htmlspecialchars(
                            $customer['billing_city'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                    >

                    <label for="billing_country">
                        Pays
                    </label>

                    <input
                        type="text"
                        id="billing_country"
                        name="billing_country"
                        value="<?= htmlspecialchars(
                            $customer['billing_country']
                                ?? 'France',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                    >

                </div>

            </div>

            <button
                type="submit"
                class="btn-primary account-submit"
            >
                Enregistrer les adresses
            </button>

        </form>

    </section>

</main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sameAddressCheckbox =
        document.getElementById('same-address');

    if (!sameAddressCheckbox) {
        return;
    }

    const copyShippingToBilling = () => {
        const fieldPairs = [
            ['shipping_address', 'billing_address'],
            ['shipping_postcode', 'billing_postcode'],
            ['shipping_city', 'billing_city'],
            ['shipping_country', 'billing_country'],
        ];

        fieldPairs.forEach(([shippingId, billingId]) => {
            const shippingField =
                document.getElementById(shippingId);

            const billingField =
                document.getElementById(billingId);

            if (shippingField && billingField) {
                billingField.value = shippingField.value;
            }
        });
    };

    sameAddressCheckbox.addEventListener(
        'change',
        () => {
            if (sameAddressCheckbox.checked) {
                copyShippingToBilling();
            }
        }
    );
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>