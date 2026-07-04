<?php
session_start();

require_once '../config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = "Merci de remplir tous les champs.";
    } else {
        $query = $pdo->prepare("
            SELECT *
            FROM customers
            WHERE email = ?
            LIMIT 1
        ");

        $query->execute([$email]);
        $customer = $query->fetch(PDO::FETCH_ASSOC);

        if ($customer && password_verify($password, $customer['password'])) {
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_firstname'] = $customer['firstname'];
            $_SESSION['customer_lastname'] = $customer['lastname'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion | Below Dreams</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<main class="account-page">
    <section class="account-card">
        <h1>Connexion</h1>
        <p>Connectez-vous à votre espace client Below Dreams.</p>

        <?php if (!empty($error)) : ?>
            <p class="account-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="account-form">

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn-primary">
                Se connecter
            </button>
        </form>

        <p class="account-switch">
            Pas encore de compte ?
            <a href="register.php">Créer un compte</a>
        </p>
    </section>
</main>

</body>
</html>
