<?php
session_start();

require_once '../config/database.php';
require_once '../includes/mailer.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Veuillez renseigner votre adresse email.";
    } else {
        $query = $pdo->prepare("
            SELECT id, email
            FROM admins
            WHERE email = ?
            LIMIT 1
        ");
        $query->execute([$email]);
        $admin = $query->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $pdo->prepare("
                UPDATE admins
                SET reset_token = ?,
                    reset_token_expires_at = ?
                WHERE id = ?
            ");

            $update->execute([
                $token,
                $expiresAt,
                $admin['id']
            ]);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

            $resetLink = $protocol . '://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF'])
                . '/reset-password.php?token=' . urlencode($token);

            sendAdminPasswordResetEmail(
                $admin['email'],
                $admin['name'],
                $resetLink
            );
        }

        $message = "Si cette adresse email existe, un lien de réinitialisation vient d'être envoyé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié | Below Dreams</title>
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

        <h1>Mot de passe oublié</h1>
        <p>Entrez votre email admin pour générer un lien de réinitialisation.</p>

        <?php if (!empty($error)) : ?>
            <div class="admin-login-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)) : ?>
            <div class="admin-login-success">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-login-form">

            <div>
                <label for="email">Email admin</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                >
            </div>

            <button type="submit">
                Générer le lien
            </button>

            <div class="admin-forgot-password">
                <a href="login.php">Retour à la connexion</a>
            </div>

        </form>

    </section>

</main>

</body>
</html>