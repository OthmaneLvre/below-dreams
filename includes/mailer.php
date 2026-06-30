<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

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
        $body
    );
}