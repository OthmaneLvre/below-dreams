<?php
session_start();

require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

$success = '';

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $query = $pdo->prepare("
        SELECT *
        FROM admins
        WHERE email = ?
        LIMIT 1
    ");

    $query->execute([$email]);
    $admin = $query->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];

        header('Location: dashboard.php');
        exit;
    }

    $error = "Email ou mot de passe incorrect.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion admin | Below Dreams</title>
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

        <h1>Administration</h1>
        <p>Connectez-vous pour gérer la boutique Below Dreams.</p>

        <?php if (!empty($success)) : ?>
            <div class="admin-login-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="admin-login-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-login-form">

            <div>
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                >
            </div>

            <div>
                <label for="password">Mot de passe</label>

                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
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

            <div class="admin-forgot-password">
                <a href="forgot-password.php">Mot de passe oublié ?</a>
            </div>

            <button type="submit">
                Se connecter
            </button>

        </form>

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