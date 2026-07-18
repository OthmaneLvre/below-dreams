<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email-template.php';

function sendMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    ?string $replyToName = null
): bool
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

        /*
        |--------------------------------------------------------------------------
        | Adresse de réponse facultative
        |--------------------------------------------------------------------------
        |
        | L'expéditeur SMTP reste contact@belowdreams.com.
        | Le Reply-To permet de répondre directement au visiteur.
        |
        */

        if (
            $replyToEmail !== null
            && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $mail->addReplyTo(
                $replyToEmail,
                $replyToName ?? ''
            );
        }

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
            orders.subtotal,
            orders.shipping_price,
            orders.shipping_method_name,
            orders.shipping_carrier,
            orders.shipping_address_snapshot,
            orders.total,
            customers.firstname,
            customers.lastname,
            customers.email
        FROM orders
        INNER JOIN customers
            ON orders.customer_id = customers.id
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
        ORDER BY id ASC
    ");

    $itemsQuery->execute([$orderId]);
    $items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

    $customerName = trim(
        ($order['firstname'] ?? '')
        . ' '
        . ($order['lastname'] ?? '')
    );

    $subject = 'Confirmation de votre commande '
        . $order['order_number'];

    /*
    |--------------------------------------------------------------------------
    | Articles
    |--------------------------------------------------------------------------
    */

    $itemsHtml = '';

    foreach ($items as $item) {
        $lineTotal = (float) $item['price']
            * (int) $item['quantity'];

        $itemsHtml .= "
            <tr>
                <td style='padding:12px;border-bottom:1px solid #eeeeee;'>
                    <strong>"
                        . htmlspecialchars($item['product_name'])
                        . "
                    </strong>

                    <br>

                    <span style='color:#777777;font-size:14px;'>
                        Taille :
                        " . htmlspecialchars($item['size']) . "
                    </span>
                </td>

                <td
                    style='padding:12px;
                    border-bottom:1px solid #eeeeee;
                    text-align:center;'
                >
                    " . (int) $item['quantity'] . "
                </td>

                <td
                    style='padding:12px;
                    border-bottom:1px solid #eeeeee;
                    text-align:right;'
                >
                    " . number_format(
                        $lineTotal,
                        2,
                        ',',
                        ' '
                    ) . " €
                </td>
            </tr>
        ";
    }

    /*
    |--------------------------------------------------------------------------
    | Livraison
    |--------------------------------------------------------------------------
    */

    $shippingPrice = (float) ($order['shipping_price'] ?? 0);

    $shippingPriceLabel = $shippingPrice === 0.0
        ? 'Gratuite'
        : number_format($shippingPrice, 2, ',', ' ') . ' €';

    $shippingMethodName = !empty($order['shipping_method_name'])
        ? htmlspecialchars($order['shipping_method_name'])
        : 'Non renseigné';

    $shippingCarrier = !empty($order['shipping_carrier'])
        ? htmlspecialchars($order['shipping_carrier'])
        : '';

    /*
    |--------------------------------------------------------------------------
    | Adresse enregistrée au moment de la commande
    |--------------------------------------------------------------------------
    */

    $shippingAddressHtml = '';

    if (!empty($order['shipping_address_snapshot'])) {
        $shippingAddress = json_decode(
            $order['shipping_address_snapshot'],
            true
        );

        if (is_array($shippingAddress)) {
            $fullName = trim(
                ($shippingAddress['firstname'] ?? '')
                . ' '
                . ($shippingAddress['lastname'] ?? '')
            );

            $address = trim(
                (string) ($shippingAddress['address'] ?? '')
            );

            $postcode = trim(
                (string) ($shippingAddress['postcode'] ?? '')
            );

            $city = trim(
                (string) ($shippingAddress['city'] ?? '')
            );

            $country = trim(
                (string) ($shippingAddress['country'] ?? '')
            );

            $phone = trim(
                (string) ($shippingAddress['phone'] ?? '')
            );

            $shippingAddressHtml = "
                <div
                    style='margin:24px 0;
                    padding:20px;
                    background:#f7f7f7;
                    border:1px solid #eeeeee;
                    border-radius:8px;'
                >
                    <h2
                        style='margin:0 0 14px;
                        font-size:18px;
                        color:#111111;'
                    >
                        Adresse de livraison
                    </h2>
            ";

            if ($fullName !== '') {
                $shippingAddressHtml .= "
                    <p style='margin:0 0 6px;'>
                        <strong>"
                            . htmlspecialchars($fullName)
                            . "
                        </strong>
                    </p>
                ";
            }

            if ($address !== '') {
                $shippingAddressHtml .= "
                    <p style='margin:0 0 6px;'>
                        "
                        . nl2br(htmlspecialchars($address))
                        . "
                    </p>
                ";
            }

            if ($postcode !== '' || $city !== '') {
                $shippingAddressHtml .= "
                    <p style='margin:0 0 6px;'>
                        "
                        . htmlspecialchars(
                            trim($postcode . ' ' . $city)
                        )
                        . "
                    </p>
                ";
            }

            if ($country !== '') {
                $shippingAddressHtml .= "
                    <p style='margin:0 0 6px;'>
                        " . htmlspecialchars($country) . "
                    </p>
                ";
            }

            if ($phone !== '') {
                $shippingAddressHtml .= "
                    <p style='margin:10px 0 0;color:#777777;'>
                        Téléphone :
                        " . htmlspecialchars($phone) . "
                    </p>
                ";
            }

            $shippingAddressHtml .= '</div>';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Corps de l’email
    |--------------------------------------------------------------------------
    */

    $body = "
        <h1>Merci pour votre commande</h1>

        <p>
            Bonjour "
            . htmlspecialchars($order['firstname'])
            . ",
        </p>

        <p>
            Votre commande
            <strong>"
                . htmlspecialchars($order['order_number'])
                . "
            </strong>
            a bien été confirmée.
        </p>

        <table
            width='100%'
            cellpadding='0'
            cellspacing='0'
            style='border-collapse:collapse;
            margin:30px 0;
            background:#ffffff;'
        >

            <thead>
                <tr>
                    <th
                        align='left'
                        style='padding:12px;
                        border-bottom:2px solid #111111;'
                    >
                        Article
                    </th>

                    <th
                        align='center'
                        style='padding:12px;
                        border-bottom:2px solid #111111;'
                    >
                        Qté
                    </th>

                    <th
                        align='right'
                        style='padding:12px;
                        border-bottom:2px solid #111111;'
                    >
                        Total
                    </th>
                </tr>
            </thead>

            <tbody>
                {$itemsHtml}
            </tbody>

            <tfoot>
                <tr>
                    <td
                        colspan='2'
                        style='padding:12px;
                        text-align:right;
                        color:#555555;'
                    >
                        Sous-total
                    </td>

                    <td
                        style='padding:12px;
                        text-align:right;'
                    >
                        "
                        . number_format(
                            (float) $order['subtotal'],
                            2,
                            ',',
                            ' '
                        )
                        . " €
                    </td>
                </tr>

                <tr>
                    <td
                        colspan='2'
                        style='padding:12px;
                        text-align:right;
                        color:#555555;'
                    >
                        Livraison
                    </td>

                    <td
                        style='padding:12px;
                        text-align:right;'
                    >
                        {$shippingPriceLabel}
                    </td>
                </tr>

                <tr>
                    <td
                        colspan='2'
                        style='padding:16px;
                        text-align:right;
                        border-top:1px solid #eeeeee;'
                    >
                        <strong>Total payé</strong>
                    </td>

                    <td
                        style='padding:16px;
                        text-align:right;
                        border-top:1px solid #eeeeee;'
                    >
                        <strong>
                            "
                            . number_format(
                                (float) $order['total'],
                                2,
                                ',',
                                ' '
                            )
                            . " €
                        </strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div
            style='margin:24px 0;
            padding:20px;
            background:#f7f7f7;
            border:1px solid #eeeeee;
            border-radius:8px;'
        >
            <h2
                style='margin:0 0 14px;
                font-size:18px;
                color:#111111;'
            >
                Mode de livraison
            </h2>

            <p style='margin:0 0 6px;'>
                <strong>{$shippingMethodName}</strong>
            </p>
    ";

    if ($shippingCarrier !== '') {
        $body .= "
            <p style='margin:0;color:#777777;'>
                {$shippingCarrier}
            </p>
        ";
    }

    $body .= "
        </div>

        {$shippingAddressHtml}

        <p>
            Nous préparons votre commande avec soin.
            Vous recevrez un nouvel email dès son expédition.
        </p>

        <p style='text-align:center;margin:35px 0;'>
            <a
                href='https://belowdreams.com/account/orders.php'
                style='background:#111111;
                color:#ffffff;
                padding:14px 28px;
                text-decoration:none;
                border-radius:6px;
                display:inline-block;'
            >
                Voir mes commandes
            </a>
        </p>

        <p>
            Merci pour votre confiance,
            <br>
            <strong>L’équipe Below Dreams</strong>
        </p>
    ";

    return sendMail(
        $order['email'],
        $customerName,
        $subject,
        getEmailTemplate(
            'Confirmation de commande',
            $body
        )
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
            <a href=\"https://belowdreams.com/account/login.php\">
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