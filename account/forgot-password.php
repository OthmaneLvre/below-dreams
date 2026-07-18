<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Veuillez renseigner votre adresse email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } elseif (mb_strlen($email) > 190) {
        $error = "L’adresse email renseignée est trop longue.";
    } else {
        $email = mb_strtolower($email);

        $query = $pdo->prepare("
            SELECT
                id,
                firstname,
                lastname,
                email
            FROM customers
            WHERE email = ?
            LIMIT 1
        ");

        $query->execute([$email]);
        $customer = $query->fetch(PDO::FETCH_ASSOC);

        /*
        |--------------------------------------------------------------------------
        | Réponse générique
        |--------------------------------------------------------------------------
        |
        | On affiche toujours le même message, que le compte existe ou non.
        | Cela évite de révéler les adresses enregistrées.
        |
        */

        $success =
            "Si un compte correspond à cette adresse, "
            . "un email de réinitialisation vient d’être envoyé.";

        if ($customer) {
            try {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);

                $expiresAt = (new DateTimeImmutable('+1 hour'))
                    ->format('Y-m-d H:i:s');

                $update = $pdo->prepare("
                    UPDATE customers
                    SET
                        password_reset_token_hash = ?,
                        password_reset_expires_at = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $tokenHash,
                    $expiresAt,
                    (int) $customer['id']
                ]);

                $resetUrl =
                    'https://belowdreams.com/account/reset-password.php'
                    . '?token='
                    . urlencode($rawToken);

                $customerName = trim(
                    ($customer['firstname'] ?? '')
                    . ' '
                    . ($customer['lastname'] ?? '')
                );

                $subject = 'Réinitialisation de votre mot de passe';

                $body = "
                    <h1>Réinitialisation de votre mot de passe</h1>

                    <p>
                        Bonjour "
                        . htmlspecialchars(
                            $customer['firstname'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        . ",
                    </p>

                    <p>
                        Une demande de réinitialisation du mot de passe
                        de votre compte Below Dreams a été effectuée.
                    </p>

                    <p style='text-align:center;margin:35px 0;'>
                        <a
                            href='"
                            . htmlspecialchars(
                                $resetUrl,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            . "'
                            style='background:#111111;
                            color:#ffffff;
                            padding:14px 28px;
                            text-decoration:none;
                            border-radius:6px;
                            display:inline-block;'
                        >
                            Réinitialiser mon mot de passe
                        </a>
                    </p>

                    <p>
                        Ce lien est valable pendant une heure.
                    </p>

                    <p>
                        Si vous n’êtes pas à l’origine de cette demande,
                        vous pouvez ignorer cet email.
                    </p>

                    <p>
                        L’équipe Below Dreams
                    </p>
                ";

                sendMail(
                    $customer['email'],
                    $customerName,
                    $subject,
                    getEmailTemplate(
                        'Réinitialisation du mot de passe',
                        $body
                    )
                );
            } catch (Throwable $e) {
                error_log(
                    'Erreur mot de passe oublié Below Dreams : '
                    . $e->getMessage()
                );
            }
        }

        $_POST = [];
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

    <title>Mot de passe oublié | Below Dreams</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>

<body>

<main class="account-page">
    <section class="account-card">

        <h1>Mot de passe oublié</h1>

        <p>
            Renseignez votre adresse email pour recevoir un lien
            de réinitialisation.
        </p>

        <?php if ($success !== '') : ?>
            <p class="account-success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

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

            <label for="email">Adresse email</label>

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

            <button type="submit" class="btn-primary">
                Envoyer le lien
            </button>

        </form>

        <p class="account-switch">
            <a href="login.php">
                Retour à la connexion
            </a>
        </p>

    </section>
</main>

</body>
</html>