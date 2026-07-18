<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (
        $firstname === ''
        || $lastname === ''
        || $email === ''
        || $password === ''
        || $passwordConfirm === ''
    ) {
        $error = "Merci de remplir tous les champs obligatoires.";
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
    } elseif ($password !== $passwordConfirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 12) {
        $error = "Le mot de passe doit contenir au moins 12 caractères.";
    } else {
        $email = mb_strtolower($email);

        $check = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE email = ?
            LIMIT 1
        ");

        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Un compte existe déjà avec cette adresse email.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $query = $pdo->prepare("
                INSERT INTO customers (
                    firstname,
                    lastname,
                    email,
                    phone,
                    password
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $query->execute([
                $firstname,
                $lastname,
                $email,
                $phone !== '' ? $phone : null,
                $hashedPassword
            ]);

            $customerId = (int) $pdo->lastInsertId();

            try {
                sendCustomerWelcomeEmail(
                    $email,
                    trim($firstname . ' ' . $lastname)
                );
            } catch (Throwable $e) {
                error_log(
                    'Erreur email de bienvenue Below Dreams : '
                    . $e->getMessage()
                );
            }

            regenerateSession();

            $_SESSION['customer_id'] = $customerId;
            $_SESSION['customer_firstname'] = $firstname;
            $_SESSION['customer_lastname'] = $lastname;

            header('Location: dashboard.php');
            exit;
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

    <title>Créer un compte | Below Dreams</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>

<body>

<main class="account-page">
    <section class="account-card">

        <h1>Créer un compte</h1>

        <p>
            Créez votre espace client Below Dreams.
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

            <label for="firstname">Prénom *</label>

            <input
                type="text"
                id="firstname"
                name="firstname"
                value="<?= htmlspecialchars(
                    $_POST['firstname'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                maxlength="100"
                autocomplete="given-name"
                required
            >

            <label for="lastname">Nom *</label>

            <input
                type="text"
                id="lastname"
                name="lastname"
                value="<?= htmlspecialchars(
                    $_POST['lastname'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                maxlength="100"
                autocomplete="family-name"
                required
            >

            <label for="email">Email *</label>

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
                autocomplete="email"
                required
            >

            <label for="phone">Téléphone</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars(
                    $_POST['phone'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                maxlength="30"
                autocomplete="tel"
            >

            <label for="password">Mot de passe *</label>

            <div class="password-field">
                <input
                    type="password"
                    name="password"
                    id="password"
                    minlength="12"
                    autocomplete="new-password"
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

            <label for="password_confirm">
                Confirmer le mot de passe *
            </label>

            <div class="password-field">
                <input
                    type="password"
                    name="password_confirm"
                    id="password_confirm"
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

            <p class="account-privacy-note">
                Les informations saisies sont utilisées pour créer et
                gérer votre compte. Consultez notre
                <a href="../politique-confidentialite.php">
                    politique de confidentialité
                </a>.
            </p>

            <button type="submit" class="btn-primary">
                Créer mon compte
            </button>

        </form>

        <p class="account-switch">
            Déjà un compte ?
            <a href="login.php">
                Se connecter
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