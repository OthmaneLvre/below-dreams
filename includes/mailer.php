<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email-template.php';

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $config = require __DIR__ . '/../config/mail.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}

function sendOrderConfirmationEmail(PDO $pdo, int $orderId): bool
{
    $query = $pdo->prepare("
        SELECT
            orders.order_number,
            orders.total,
            customers.firstname,
            customers.lastname,
            customers.email
        FROM orders
        INNER JOIN customers ON orders.customer_id = customers.id
        WHERE orders.id = ?
        LIMIT 1
    ");

    $query->execute([$orderId]);
    $order = $query->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        return false;
    }

    $customerName = trim($order['firstname'] . ' ' . $order['lastname']);

    $subject = "Confirmation de votre commande " . $order['order_number'];

    $body = "
        <h1>Merci pour votre commande</h1>

        <p>Bonjour " . htmlspecialchars($order['firstname']) . ",</p>

        <p>Votre commande <strong>" . htmlspecialchars($order['order_number']) . "</strong> a bien été confirmée.</p>

        <p>
            Montant total :
            <strong>" . number_format((float) $order['total'], 2, ',', ' ') . " €</strong>
        </p>

        <p>Nous vous informerons dès que votre commande sera expédiée.</p>

        <p>Merci pour votre confiance,<br>L'équipe Below Dreams</p>
    ";

    return sendMail(
        $order['email'],
        $customerName,
        $subject,
        getEmailTemplate('Confirmation de commande', $body)
    );
}

function sendAdminPasswordResetEmail(
    string $adminEmail,
    string $adminName,
    string $resetLink
): bool
{
    $subject = "Réinitialisation de votre mot de passe administrateur";

    $body = "
        <h1>Réinitialisation du mot de passe</h1>

        <p>Bonjour,</p>

        <p>Vous avez demandé la réinitialisation de votre mot de passe administrateur Below Dreams.</p>

        <p>
            <a href=\"" . htmlspecialchars($resetLink) . "\">
                Réinitialiser mon mot de passe
            </a>
        </p>

        <p>Ce lien est valable pendant 1 heure.</p>

        <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.</p>

        <p>L'équipe Below Dreams</p>
    ";

    return sendMail(
        $adminEmail,
        $adminName,
        $subject,
        getEmailTemplate('Réinitialisation du mot de passe', $body)
    );
}

function sendCustomerWelcomeEmail(
    string $customerEmail,
    string $customerName
): bool {
    $subject = "Bienvenue chez Below Dreams";

    $body = "
        <h1>Bienvenue chez Below Dreams</h1>

        <p>Bonjour " . htmlspecialchars($customerName) . ",</p>

        <p>Votre compte client a bien été créé.</p>

        <p>Vous pouvez maintenant accéder à votre espace client, suivre vos commandes et profiter de votre boutique Below Dreams.</p>

        <p>
            <a href=\"https://belowdreams.fr/account/login.php\">
                Accéder à mon espace client
            </a>
        </p>

        <p>À très bientôt,<br>L'équipe Below Dreams</p>
    ";

    return sendMail(
        $customerEmail,
        $customerName,
        $subject,
        getEmailTemplate('Bienvenue chez Below Dreams', $body)
    );
}