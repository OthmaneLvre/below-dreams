<?php
session_start();

require_once '../config/database.php';

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
    <title>Administration Below Dreams</title>
</head>
<body>

    <h1>Connexion Administrateur</h1>

    <?php if (!empty($error)) : ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Email</label><br>
        <input type="email" name="email" required><br><br>

        <label>Mot de passe</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">
            Se connecter
        </button>

    </form>

</body>
</html>