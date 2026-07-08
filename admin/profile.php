<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

$query = $pdo->prepare("
    SELECT *
    FROM admins
    WHERE id = ?
    LIMIT 1
");

$query->execute([$_SESSION['admin_id']]);
$admin = $query->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $error = "Le nom affiché est obligatoire.";
    }

    if (!$error && !empty($newPassword)) {
        if (!password_verify($currentPassword, $admin['password'])) {
            $error = "Le mot de passe actuel est incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Les nouveaux mots de passe ne correspondent pas.";
        } elseif (strlen($newPassword) < 8) {
            $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
    }

    if (!$error) {
        if (!empty($newPassword)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $pdo->prepare("
                UPDATE admins
                SET firstname = ?, lastname = ?, name = ?, password = ?
                WHERE id = ?
            ");

            $update->execute([
                $firstname,
                $lastname,
                $name,
                $passwordHash,
                $_SESSION['admin_id']
            ]);
        } else {
            $update = $pdo->prepare("
                UPDATE admins
                SET firstname = ?, lastname = ?, name = ?
                WHERE id = ?
            ");

            $update->execute([
                $firstname,
                $lastname,
                $name,
                $_SESSION['admin_id']
            ]);
        }

        $_SESSION['admin_name'] = $name;

        $success = "Profil mis à jour avec succès.";

        $query->execute([$_SESSION['admin_id']]);
        $admin = $query->fetch(PDO::FETCH_ASSOC);
    }
}

$pageTitle = "Profil administrateur | Below Dreams";

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">
        <div>
            <h1>Mon profil</h1>
            <p>Gérez vos informations administrateur.</p>
        </div>
    </header>

    <section class="admin-section">

        <?php if (!empty($success)) : ?>
            <div class="admin-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="admin-alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-form">

            <div class="form-grid">

                <div class="form-group">
                    <label>Prénom</label>
                    <input
                        type="text"
                        name="firstname"
                        value="<?= htmlspecialchars($admin['firstname'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nom</label>
                    <input
                        type="text"
                        name="lastname"
                        value="<?= htmlspecialchars($admin['lastname'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nom affiché *</label>
                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($admin['name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <input
                        type="text"
                        value="<?= htmlspecialchars($admin['role'] ?? 'admin') ?>"
                        disabled
                    >
                </div>

            </div>

            <hr class="admin-separator">

            <h2>Modifier le mot de passe</h2>

            <div class="form-grid">

                <div class="form-group">
                    <label>Mot de passe actuel</label>

                    <div class="password-field">
                        <input type="password" name="current_password" id="current_password">

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="current_password"
                            aria-label="Afficher le mot de passe"
                        >
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nouveau mot de passe</label>

                    <div class="password-field">
                        <input type="password" name="new_password" id="new_password">

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="new_password"
                            aria-label="Afficher le mot de passe"
                        >
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>

                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirm_password">

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirm_password"
                            aria-label="Afficher le mot de passe"
                        >
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn">
                    Enregistrer les modifications
                </button>
            </div>

        </form>

    </section>

</main>

<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon = this.querySelector('.eye-icon');

        if (!input || !icon) {
            return;
        }

        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';
        icon.textContent = isPassword ? '🙈' : '👁️';
        this.setAttribute(
            'aria-label',
            isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
        );
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>
