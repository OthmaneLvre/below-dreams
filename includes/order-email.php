<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

/**
 * Tente d'envoyer la confirmation d'une commande payée.
 *
 * Retourne true uniquement si l'envoi SMTP réussit.
 */
function processOrderConfirmationEmail(
    PDO $pdo,
    int $orderId
): bool {
    /*
    |--------------------------------------------------------------------------
    | Réservation atomique de l'envoi
    |--------------------------------------------------------------------------
    */

    $claim = $pdo->prepare("
        UPDATE orders
        SET
            confirmation_email_status = 'sending',
            confirmation_email_attempts =
                confirmation_email_attempts + 1,
            confirmation_email_started_at = NOW()
        WHERE id = ?
        AND payment_status = 'paid'
        AND stock_reservation_status = 'confirmed'
        AND confirmation_email_sent_at IS NULL
        AND confirmation_email_attempts < 3
        AND (
            confirmation_email_status = 'pending'
            OR (
                confirmation_email_status = 'failed'
                AND confirmation_email_started_at
                    <= NOW() - INTERVAL 15 MINUTE
            )
        )
    ");

    $claim->execute([$orderId]);

    if ($claim->rowCount() !== 1) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Envoi hors transaction SQL
    |--------------------------------------------------------------------------
    */

    $sent = false;

    try {
        $sent = sendOrderConfirmationEmail(
            $pdo,
            $orderId
        );
    } catch (Throwable $e) {
        error_log(
            '[ORDER EMAIL] Order '
            . $orderId
            . ' | '
            . $e->getMessage()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Enregistrement du résultat
    |--------------------------------------------------------------------------
    */

    if ($sent) {
        $update = $pdo->prepare("
            UPDATE orders
            SET
                confirmation_email_status = 'sent',
                confirmation_email_sent_at = NOW()
            WHERE id = ?
              AND confirmation_email_status = 'sending'
              AND confirmation_email_sent_at IS NULL
        ");

        $update->execute([$orderId]);

        return $update->rowCount() === 1;
    }

    $update = $pdo->prepare("
        UPDATE orders
        SET confirmation_email_status = 'failed'
        WHERE id = ?
          AND confirmation_email_status = 'sending'
          AND confirmation_email_sent_at IS NULL
    ");

    $update->execute([$orderId]);

    return false;
}
