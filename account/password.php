<?php

require_once 'auth.php';
require_once '../config/database.php';

$pageTitle = "Mot de passe | Below Dreams";
$basePath = '../';

$success = '';
$error = '';

$query = $pdo->prepare("
    SELECT password
    FROM customers
    WHERE id = ?
");

$query->execute([$_SESSION['customer_id']]);
$customer = $query->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $passwordConfirm = $_POST['password_confirm'];

    if (!$currentPassword || !$newPassword || !$passwordConfirm) {
        $error = "Merci de remplir tous les champs.";
    } elseif (!password_verify($currentPassword, $customer['password'])) {
        $error = "Le mot de passe actuel est incorrect.";
    } elseif ($newPassword !== $passwordConfirm) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE customers
            SET password = ?
            WHERE id = ?
        ");

        $update->execute([
            $hashedPassword,
            $_SESSION['customer_id']
        ]);

        $success = "Votre mot de passe a bien été modifié.";
    }
}

require_once '../partials/header.php';
?>

<div class="account-area">

<?php require_once 'partials/sidebar.php'; ?>

<main class="account-main">

    <header class="account-header">
        <h1>Mot de passe</h1>
        <p>Modifiez le mot de passe de votre compte.</p>
    </header>

    <section class="account-section">

        <?php if (!empty($success)) : ?>
            <p class="account-success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <p class="account-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="account-form account-form-large">

            <div class="form-grid">

                <div>
                    <label>Mot de passe actuel *</label>
                    <input type="password" name="current_password" required>
                </div>

                <div>
                    <label>Nouveau mot de passe *</label>
                    <input type="password" name="new_password" required>
                </div>

                <div>
                    <label>Confirmer le nouveau mot de passe *</label>
                    <input type="password" name="password_confirm" required>
                </div>

            </div>

            <button type="submit" class="btn-primary account-submit">
                Modifier mon mot de passe
            </button>

        </form>

    </section>

</main>

</div>

<?php require_once '../partials/footer.php'; ?>
