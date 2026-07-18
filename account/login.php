<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Merci de remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email ou mot de passe incorrect.";
    } else {
        $query = $pdo->prepare("
            SELECT
                id,
                firstname,
                lastname,
                email,
                password
            FROM customers
            WHERE email = ?
            LIMIT 1
        ");

        $query->execute([$email]);

        $customer = $query->fetch(PDO::FETCH_ASSOC);

        if (
            $customer
            && password_verify(
                $password,
                $customer['password']
            )
        ) {
            regenerateSession();

            $_SESSION['customer_id'] = (int) $customer['id'];
            $_SESSION['customer_firstname'] = $customer['firstname'];
            $_SESSION['customer_lastname'] = $customer['lastname'];

            header('Location: dashboard.php');
            exit;
        }

        $error = "Email ou mot de passe incorrect.";
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

    <title>Connexion | Below Dreams</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>

<body>

<main class="account-page">
    <section class="account-card">

        <h1>Connexion</h1>

        <p>
            Connectez-vous à votre espace client Below Dreams.
        </p>

        <?php if ($error !== '') : ?>
            <p class="account-error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="account-form">

            <?= csrfField() ?>

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
                autocomplete="email"
                required
            >

            <label for="password">Mot de passe</label>

            <div class="password-field">
                <input
                    type="password"
                    name="password"
                    id="password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    data-target="password"
                    aria-label="Afficher le mot de passe"
                    aria-pressed="false"
                >
                    👁
                </button>
            </div>

            <button type="submit" class="btn-primary">
                Se connecter
            </button>

        </form>

        <p class="account-switch">
            Pas encore de compte ?
            <a href="register.php">
                Créer un compte
            </a>
        </p>

        <p class="account-switch">
            <a href="forgot-password.php">
                Mot de passe oublié ?
            </a>
        </p>

    </section>
</main>

<script>
document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);

        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        button.textContent = isHidden ? '🙈' : '👁';
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
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