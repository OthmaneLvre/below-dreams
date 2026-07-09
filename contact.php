<?php
session_start();

require_once 'includes/mailer.php';

$pageTitle = "Contact | Below Dreams";
$pageDescription = "Contactez Below Dreams pour une question sur une commande, une livraison, un retour ou une demande d'information.";

$basePath = '';

$success = '';
$error = '';

$mailConfig = require __DIR__ . '/config/mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $requestType = trim($_POST['request_type'] ?? '');
    $orderNumber = trim($_POST['order_number'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $website = trim($_POST['website'] ?? '');

    if (!empty($website)) {
        $error = "Une erreur est survenue. Veuillez réessayer.";
    } elseif ($name === '' || $email === '' || $requestType === '' || $message === '') {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } elseif (strlen($message) < 10) {
        $error = "Votre message doit contenir au moins 10 caractères.";
    } else {
        $types = [
            'commande' => 'Question sur une commande',
            'livraison' => 'Question sur une livraison',
            'retour' => 'Retour / échange',
            'information' => 'Demande d’information',
            'autre' => 'Autre demande'
        ];

        $requestLabel = $types[$requestType] ?? 'Demande de contact';

        $subject = "Contact Below Dreams - " . $requestLabel;

        $body = "
            <h1>Nouveau message de contact</h1>

            <p><strong>Nom :</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email :</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Type de demande :</strong> " . htmlspecialchars($requestLabel) . "</p>
            <p><strong>Commande :</strong> " . htmlspecialchars($orderNumber ?: 'Non renseignée') . "</p>

            <hr>

            <p><strong>Message :</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";

        $sent = sendMail(
            $mailConfig['from_email'],
            $mailConfig['from_name'],
            $subject,
            getEmailTemplate('Nouveau message de contact', $body)
        );

        if ($sent) {
            $success = "Votre message a bien été envoyé. Nous vous répondrons rapidement.";
            $_POST = [];
        } else {
            $error = "Impossible d’envoyer votre message pour le moment.";
        }
    }
}

require_once 'partials/header.php';
?>

<main class="contact-page">

    <section class="contact-hero">
        <div class="container contact-hero-inner">
            <h1>Contact</h1>
            <p>
                Une question sur une commande, une livraison, un retour ou une pièce Below Dreams ?
                Envoyez-nous votre demande.
            </p>
        </div>
    </section>

    <section class="contact-section">
        <div class="container contact-inner">

            <div class="contact-content">
                <span class="badge">Service client</span>

                <h2>Comment pouvons-nous vous aider ?</h2>

                <p>
                    Utilisez ce formulaire pour toute demande liée à votre commande,
                    à une livraison, à un retour ou à une information produit.
                </p>

                <div class="contact-box">
                    <h3>Commandes</h3>
                    <p>Pour une commande, indiquez votre référence afin de faciliter le suivi.</p>
                </div>

                <div class="contact-box">
                    <h3>Délais de réponse</h3>
                    <p>Nous répondons généralement sous 24 à 48h ouvrées.</p>
                </div>
            </div>

            <div class="contact-card">

                <?php if ($success) : ?>
                    <p class="account-success"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>

                <?php if ($error) : ?>
                    <p class="account-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="POST" class="contact-form">

                    <label for="name">Nom complet *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                    <label for="request_type">Type de demande *</label>
                    <select id="request_type" name="request_type" required>
                        <option value="">Sélectionner</option>
                        <option value="commande">Question sur une commande</option>
                        <option value="livraison">Question sur une livraison</option>
                        <option value="retour">Retour / échange</option>
                        <option value="information">Demande d’information</option>
                        <option value="autre">Autre demande</option>
                    </select>

                    <label for="order_number">Référence commande</label>
                    <input type="text" id="order_number" name="order_number" value="<?= htmlspecialchars($_POST['order_number'] ?? '') ?>" placeholder="Ex : BD-2026-001">

                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="7" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

                    <div class="contact-honeypot">
                        <label for="website">Site web</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit" class="btn-primary contact-submit">
                        Envoyer le message
                    </button>

                </form>
            </div>

        </div>
    </section>

</main>

<?php require_once 'partials/footer.php'; ?>