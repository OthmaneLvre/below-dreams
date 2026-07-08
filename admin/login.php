<?php
session_start();

require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

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
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit">
                    Se connecter
                </button>

            </form>

        </section>

    </main>

</body>
</html>