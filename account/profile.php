<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Mes informations | Below Dreams";
$basePath = '../';

$success = '';
$error = '';

$query = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
");

$query->execute([$_SESSION['customer_id']]);
$customer = $query->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (!$firstname || !$lastname || !$email) {
        $error = "Merci de remplir les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        $check = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE email = ?
            AND id != ?
        ");

        $check->execute([$email, $_SESSION['customer_id']]);

        if ($check->fetch()) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            $update = $pdo->prepare("
                UPDATE customers
                SET firstname = ?, lastname = ?, email = ?, phone = ?
                WHERE id = ?
            ");

            $update->execute([
                $firstname,
                $lastname,
                $email,
                $phone,
                $_SESSION['customer_id']
            ]);

            $_SESSION['customer_firstname'] = $firstname;

            $success = "Vos informations ont bien été mises à jour.";

            $query->execute([$_SESSION['customer_id']]);
            $customer = $query->fetch(PDO::FETCH_ASSOC);
        }
    }
}

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mes informations</h1>
        <p>Modifiez vos informations personnelles.</p>
    </header>

    <section class="account-section">

        <?php if (!empty($success)) : ?>
            <p class="account-success">
                <?= htmlspecialchars($success) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <p class="account-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="account-form account-form-large">

            <div class="form-grid">
                <div>
                    <label>Prénom *</label>
                    <input
                        type="text"
                        name="firstname"
                        value="<?= htmlspecialchars($customer['firstname']) ?>"
                        required
                    >
                </div>

                <div>
                    <label>Nom *</label>
                    <input
                        type="text"
                        name="lastname"
                        value="<?= htmlspecialchars($customer['lastname']) ?>"
                        required
                    >
                </div>

                <div>
                    <label>Email *</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($customer['email']) ?>"
                        required
                    >
                </div>

                <div>
                    <label>Téléphone</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                    >
                </div>
            </div>

            <button type="submit" class="btn-primary account-submit">
                Enregistrer les modifications
            </button>

        </form>

    </section>

</main>

</div>

<?php require_once '../partials/footer.php'; ?>
