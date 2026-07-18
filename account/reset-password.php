<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

$error = '';
$success = '';
$tokenIsValid = false;
$customerId = null;

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokenHash = hash('sha256', $token);

    $query = $pdo->prepare("
        SELECT id
        FROM customers
        WHERE password_reset_token_hash = ?
        AND password_reset_expires_at IS NOT NULL
        AND password_reset_expires_at > NOW()
        LIMIT 1
    ");

    $query->execute([$tokenHash]);
    $customer = $query->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $tokenIsValid = true;
        $customerId = (int) $customer['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $newPassword = $_POST['new_password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!$tokenIsValid || !$customerId) {
        $error =
            "Ce lien de réinitialisation est invalide ou a expiré.";
    } elseif (
        $newPassword === ''
        || $passwordConfirm === ''
    ) {
        $error = "Merci de remplir tous les champs.";
    } elseif ($newPassword !== $passwordConfirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 12) {
        $error =
            "Le mot de passe doit contenir au moins 12 caractères.";
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
            AND password_reset_token_hash = ?
        ");

        $update->execute([
            $hashedPassword,
            $customerId,
            hash('sha256', $token)
        ]);

        if ($update->rowCount() === 1) {
            $success =
                "Votre mot de passe a bien été réinitialisé. "
                . "Vous pouvez maintenant vous connecter.";

            $tokenIsValid = false;
        } else {
            $error =
                "Ce lien de réinitialisation n’est plus valide.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Réinitialiser mon mot de passe | Below Dreams</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>

<body>

<main class="account-page">
    <section class="account-card">

        <h1>Réinitialiser mon mot de passe</h1>

        <?php if ($success !== '') : ?>

            <p class="account-success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p class="account-switch">
                <a href="login.php">
                    Se connecter
                </a>
            </p>

        <?php elseif (!$tokenIsValid) : ?>

            <p class="account-error">
                Ce lien de réinitialisation est invalide ou a expiré.
            </p>

            <p class="account-switch">
                <a href="forgot-password.php">
                    Demander un nouveau lien
                </a>
            </p>

        <?php else : ?>

            <?php if ($error !== '') : ?>
                <p class="account-error">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            <?php endif; ?>

            <p>
                Choisissez un nouveau mot de passe d’au moins
                12 caractères.
            </p>

            <form method="POST" class="account-form">

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars(
                        $token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <label for="new_password">
                    Nouveau mot de passe
                </label>

                <div class="password-field">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="new_password"
                        aria-label="Afficher le mot de passe"
                        aria-pressed="false"
                    >
                        👁
                    </button>
                </div>

                <label for="password_confirm">
                    Confirmer le mot de passe
                </label>

                <div class="password-field">
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password_confirm"
                        aria-label="Afficher le mot de passe"
                        aria-pressed="false"
                    >
                        👁
                    </button>
                </div>

                <button type="submit" class="btn-primary">
                    Réinitialiser mon mot de passe
                </button>

            </form>

        <?php endif; ?>

    </section>
</main>

<script>
document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(
            button.dataset.target
        );

        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        button.textContent = isHidden ? '🙈' : '👁';

        button.setAttribute(
            'aria-pressed',
            isHidden ? 'true' : 'false'
        );

        button.setAttribute(
            'aria-label',
            isHidden
                ? 'Masquer le mot de passe'
                : 'Afficher le mot de passe'
        );
    });
});
</script>

</body>
</html>