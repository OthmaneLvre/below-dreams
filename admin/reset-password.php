<?php
session_start();

require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$admin = null;

if (empty($token)) {
    $error = "Lien de réinitialisation invalide.";
} else {
    $query = $pdo->prepare("
        SELECT *
        FROM admins
        WHERE reset_token = ?
        AND reset_token_expires_at > NOW()
        LIMIT 1
    ");
    $query->execute([$token]);
    $admin = $query->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $error = "Ce lien est invalide ou expiré.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($password) || empty($confirmPassword)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE admins
            SET password = ?,
                reset_token = NULL,
                reset_token_expires_at = NULL
            WHERE id = ?
        ");

        $update->execute([
            $hashedPassword,
            $admin['id']
        ]);

        header('Location: login.php?reset=success');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le mot de passe | Below Dreams</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body class="admin-login-page">

<main class="admin-login-wrapper">

    <section class="admin-login-card">

        <img
            src="../assets/logos/below-dreams-black.svg"
            alt="Below Dreams"
            class="admin-login-logo"
        >

        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un nouveau mot de passe administrateur.</p>

        <?php if (!empty($error)) : ?>
            <div class="admin-login-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($admin && empty($success)) : ?>

            <form method="POST" class="admin-login-form">

                <div>
                    <label for="password">Nouveau mot de passe</label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password"
                            aria-label="Afficher le mot de passe"
                        >
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirm_password">Confirmer le mot de passe</label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirm_password"
                            aria-label="Afficher le mot de passe"
                        >
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit">
                    Mettre à jour le mot de passe
                </button>

            </form>

        <?php endif; ?>

        <div class="admin-forgot-password">
            <a href="login.php">Retour à la connexion</a>
        </div>

    </section>

</main>

<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon = this.querySelector('.eye-icon');

        if (!input || !icon) {
            return;
        }

        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';
        icon.textContent = isPassword ? '🙈' : '👁️';
        this.setAttribute(
            'aria-label',
            isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
        );
    });
});
</script>

</body>
</html>