<?php

require_once 'auth.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = "Mot de passe | Below Dreams";
$basePath = '../';

$success = '';
$error = '';

$query = $pdo->prepare("
    SELECT password
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$query->execute([
    $_SESSION['customer_id']
]);

$customer = $query->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();

    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (
        $currentPassword === ''
        || $newPassword === ''
        || $passwordConfirm === ''
    ) {
        $error = "Merci de remplir tous les champs.";
    } elseif (
        !password_verify(
            $currentPassword,
            $customer['password']
        )
    ) {
        $error = "Le mot de passe actuel est incorrect.";
    } elseif ($newPassword !== $passwordConfirm) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 12) {
        $error =
            "Le nouveau mot de passe doit contenir au moins 12 caractères.";
    } elseif (
        password_verify(
            $newPassword,
            $customer['password']
        )
    ) {
        $error =
            "Le nouveau mot de passe doit être différent de l’ancien.";
    } else {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $update = $pdo->prepare("
            UPDATE customers
            SET
                password = ?,
                password_reset_token_hash = NULL,
                password_reset_expires_at = NULL
            WHERE id = ?
        ");

        $update->execute([
            $hashedPassword,
            (int) $_SESSION['customer_id']
        ]);

        regenerateSession();

        $customer['password'] = $hashedPassword;

        $success =
            "Votre mot de passe a bien été modifié.";
    }
}

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mot de passe</h1>

        <p>
            Modifiez le mot de passe de votre compte.
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
                    <label for="current_password">
                        Mot de passe actuel *
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div>
                    <label for="new_password">
                        Nouveau mot de passe *
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div>
                    <label for="password_confirm">
                        Confirmer le nouveau mot de passe *
                    </label>

                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                </div>

            </div>

            <button
                type="submit"
                class="btn-primary account-submit"
            >
                Modifier mon mot de passe
            </button>

        </form>

    </section>

</main>

</div>

<?php require_once '../partials/footer.php'; ?>