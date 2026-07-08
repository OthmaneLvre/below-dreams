<?php
session_start();

require_once '../config/database.php';
require_once '../includes/mailer.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['password_confirm'];

    if (!$firstname || !$lastname || !$email || !$password || !$passwordConfirm) {
        $error = "Merci de remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif ($password !== $passwordConfirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $check = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Un compte existe déjà avec cette adresse email.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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
                $phone,
                $hashedPassword
            ]);

            $customerId = $pdo->lastInsertId();

            sendCustomerWelcomeEmail(
                $email,
                trim($firstname . ' ' . $lastname)
            );

            $_SESSION['customer_id'] = $customerId;
            $_SESSION['customer_name'] = $firstname;

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
    <title>Créer un compte | Below Dreams</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<main class="account-page">
    <section class="account-card">
        <h1>Créer un compte</h1>
        <p>Créez votre espace client Below Dreams.</p>

        <?php if (!empty($error)) : ?>
            <p class="account-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="account-form">

            <label>Prénom *</label>
            <input type="text" name="firstname" required>

            <label>Nom *</label>
            <input type="text" name="lastname" required>

            <label>Email *</label>
            <input type="email" name="email" required>

            <label>Téléphone</label>
            <input type="text" name="phone">

            <label>Mot de passe *</label>
            <input type="password" name="password" required>

            <label>Confirmer le mot de passe *</label>
            <input type="password" name="password_confirm" required>

            <button type="submit" class="btn-primary">
                Créer mon compte
            </button>
        </form>

        <p class="account-switch">
            Déjà un compte ?
            <a href="login.php">Se connecter</a>
        </p>
    </section>
</main>

</body>
</html>
