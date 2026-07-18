<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/login-rate-limit.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (
    isset($_GET['reset'])
    && $_GET['reset'] === 'success'
) {
    $success =
        "Votre mot de passe a été réinitialisé avec succès. "
        . "Vous pouvez maintenant vous connecter.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = mb_strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Merci de remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email ou mot de passe incorrect.";
    } else {
        $rateLimit = getLoginRateLimitStatus(
            $pdo,
            'admin',
            $email
        );

        if ($rateLimit['locked']) {
            $error =
                "Trop de tentatives ont été effectuées. "
                . "Veuillez réessayer dans "
                . formatLoginLockDuration(
                    $rateLimit['remaining_seconds']
                )
                . ".";
        } else {
            $query = $pdo->prepare("
                SELECT
                    id,
                    name,
                    email,
                    password
                FROM admins
                WHERE email = ?
                LIMIT 1
            ");

            $query->execute([$email]);

            $admin = $query->fetch(PDO::FETCH_ASSOC);

            if (
                $admin
                && password_verify(
                    $password,
                    $admin['password']
                )
            ) {
                clearLoginAttempts(
                    $pdo,
                    'admin',
                    $email
                );

                regenerateSession();

                $_SESSION['admin_id'] =
                    (int) $admin['id'];

                $_SESSION['admin_name'] =
                    $admin['name'];

                header('Location: dashboard.php');
                exit;
            }

            recordFailedLoginAttempt(
                $pdo,
                'admin',
                $email
            );

            $updatedRateLimit = getLoginRateLimitStatus(
                $pdo,
                'admin',
                $email
            );

            if ($updatedRateLimit['locked']) {
                $error =
                    "Trop de tentatives ont été effectuées. "
                    . "Veuillez réessayer dans "
                    . LOGIN_LOCK_MINUTES
                    . " minutes.";
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
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

    <title>Connexion admin | Below Dreams</title>

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

        <p>
            Connectez-vous pour gérer la boutique Below Dreams.
        </p>

        <?php if ($success !== '') : ?>
            <div class="admin-login-success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== '') : ?>
            <div class="admin-login-error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-login-form">

            <?= csrfField() ?>

            <div>
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $_POST['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    maxlength="190"
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
                        aria-pressed="false"
                    >
                        <span class="eye-icon">👁️</span>
                    </button>
                </div>
            </div>

            <div class="admin-forgot-password">
                <a href="forgot-password.php">
                    Mot de passe oublié ?
                </a>
            </div>

            <button type="submit">
                Se connecter
            </button>

        </form>

    </section>

</main>

<script>
document
    .querySelectorAll('.password-toggle')
    .forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(
                button.dataset.target
            );

            const icon = button.querySelector('.eye-icon');

            if (!input || !icon) {
                return;
            }

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            icon.textContent = isHidden ? '🙈' : '👁️';

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