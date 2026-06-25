<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

    <h1>
        Bienvenue <?= htmlspecialchars($_SESSION['admin_name']) ?>
    </h1>

    <p>Administration Below Dreams</p>

    <a href="logout.php">
        Déconnexion
    </a>

</body>
</html>