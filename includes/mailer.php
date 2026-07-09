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

    $itemsQuery = $pdo->prepare("
        SELECT
            product_name,
            size,
            quantity,
            price
        FROM order_items
        WHERE order_id = ?
    ");

    $itemsQuery->execute([$orderId]);
    $items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

    $customerName = trim($order['firstname'] . ' ' . $order['lastname']);
    $subject = "Confirmation de votre commande " . $order['order_number'];

    $itemsHtml = "";

    foreach ($items as $item) {
        $lineTotal = (float) $item['price'] * (int) $item['quantity'];

        $itemsHtml .= "
            <tr>
                <td style='padding:12px;border-bottom:1px solid #eeeeee;'>
                    <strong>" . htmlspecialchars($item['product_name']) . "</strong><br>
                    <span style='color:#777;font-size:14px;'>
                        Taille : " . htmlspecialchars($item['size']) . "
                    </span>
                </td>

                <td style='padding:12px;border-bottom:1px solid #eeeeee;text-align:center;'>
                    " . (int) $item['quantity'] . "
                </td>

                <td style='padding:12px;border-bottom:1px solid #eeeeee;text-align:right;'>
                    " . number_format($lineTotal, 2, ',', ' ') . " €
                </td>
            </tr>
        ";
    }

    $body = "
        <h1>Merci pour votre commande</h1>

        <p>Bonjour " . htmlspecialchars($order['firstname']) . ",</p>

        <p>
            Votre commande <strong>" . htmlspecialchars($order['order_number']) . "</strong>
            a bien été confirmée.
        </p>

        <table width='100%' cellpadding='0' cellspacing='0'
            style='border-collapse:collapse;margin:30px 0;background:#ffffff;'>

            <thead>
                <tr>
                    <th align='left' style='padding:12px;border-bottom:2px solid #111111;'>
                        Article
                    </th>
                    <th align='center' style='padding:12px;border-bottom:2px solid #111111;'>
                        Qté
                    </th>
                    <th align='right' style='padding:12px;border-bottom:2px solid #111111;'>
                        Total
                    </th>
                </tr>
            </thead>

            <tbody>
                {$itemsHtml}
            </tbody>

            <tfoot>
                <tr>
                    <td colspan='2' style='padding:16px;text-align:right;'>
                        <strong>Total payé</strong>
                    </td>
                    <td style='padding:16px;text-align:right;'>
                        <strong>" . number_format((float) $order['total'], 2, ',', ' ') . " €</strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p>
            Nous préparons votre commande avec soin.
            Vous recevrez un nouvel email dès son expédition.
        </p>

        <p style='text-align:center;margin:35px 0;'>
            <a href='https://belowdreams.fr/account/orders.php'
                style='background:#111111;color:#ffffff;padding:14px 28px;
                text-decoration:none;border-radius:6px;display:inline-block;'>
                Voir mes commandes
            </a>
        </p>

        <p>
            Merci pour votre confiance,<br>
            <strong>L'équipe Below Dreams</strong>
        </p>
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