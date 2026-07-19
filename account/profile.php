<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = "Mes informations | Below Dreams";
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

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if (
        $firstname === ''
        || $lastname === ''
        || $email === ''
    ) {
        $error = "Merci de remplir les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (mb_strlen($firstname) > 100) {
        $error = "Le prénom renseigné est trop long.";
    } elseif (mb_strlen($lastname) > 100) {
        $error = "Le nom renseigné est trop long.";
    } elseif (mb_strlen($email) > 190) {
        $error = "L'adresse email renseignée est trop longue.";
    } elseif (mb_strlen($phone) > 30) {
        $error = "Le numéro de téléphone renseigné est trop long.";
    } else {
        $check = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $check->execute([
            $email,
            (int) $_SESSION['customer_id']
        ]);

        if ($check->fetch()) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            $update = $pdo->prepare("
                UPDATE customers
                SET
                    firstname = ?,
                    lastname = ?,
                    email = ?,
                    phone = ?
                WHERE id = ?
            ");

            $update->execute([
                $firstname,
                $lastname,
                $email,
                $phone !== '' ? $phone : null,
                (int) $_SESSION['customer_id']
            ]);

            $_SESSION['customer_firstname'] = $firstname;
            $_SESSION['customer_lastname'] = $lastname;

            $success =
                "Vos informations ont bien été mises à jour.";

            $query->execute([
                (int) $_SESSION['customer_id']
            ]);

            $customer = $query->fetch(PDO::FETCH_ASSOC);
        }
    }
}

require_once __DIR__ . '/../partials/header.php';
?>

<div class="account-area">

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mes informations</h1>

        <p>
            Modifiez vos informations personnelles.
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

            <div class="form-grid">

                <div>
                    <label for="firstname">Prénom *</label>

                    <input
                        type="text"
                        id="firstname"
                        name="firstname"
                        value="<?= htmlspecialchars(
                            $customer['firstname'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                        autocomplete="given-name"
                        required
                    >
                </div>

                <div>
                    <label for="lastname">Nom *</label>

                    <input
                        type="text"
                        id="lastname"
                        name="lastname"
                        value="<?= htmlspecialchars(
                            $customer['lastname'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="100"
                        autocomplete="family-name"
                        required
                    >
                </div>

                <div>
                    <label for="email">Email *</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $customer['email'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="190"
                        autocomplete="email"
                        required
                    >
                </div>

                <div>
                    <label for="phone">Téléphone</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $customer['phone'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="30"
                        autocomplete="tel"
                    >
                </div>

            </div>

            <button
                type="submit"
                class="btn-primary account-submit"
            >
                Enregistrer les modifications
            </button>

        </form>

    </section>

</main>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>