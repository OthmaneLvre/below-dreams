<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/order-email.php';

$mode = $argv[1] ?? null;

if (
    $argc !== 2
    || !in_array($mode, ['--dry-run', '--execute'], true)
) {
    fwrite(
        STDERR,
        "Usage: php reconcile-order-emails.php "
        . "[--dry-run|--execute]\n"
    );
    exit(1);
}

$execute = $mode === '--execute';
$batchSize = 50;

function orderEmailCronLog(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] '
        . $message . PHP_EOL;
}

$stats = [
    'eligible' => 0,
    'sent' => 0,
    'not_sent' => 0,
    'blocked' => 0,
    'errors' => 0,
];

orderEmailCronLog(
    'Démarrage réconciliation e-mails | Mode : '
    . ($execute ? 'EXECUTE' : 'DRY-RUN')
);

/*
|--------------------------------------------------------------------------
| Commandes éligibles
|--------------------------------------------------------------------------
*/

$query = $pdo->prepare("
    SELECT
        id,
        order_number,
        confirmation_email_status,
        confirmation_email_attempts
    FROM orders
    WHERE payment_status = 'paid'
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
    ORDER BY id ASC
    LIMIT {$batchSize}
");

$query->execute();
$orders = $query->fetchAll(PDO::FETCH_ASSOC);

$stats['eligible'] = count($orders);

orderEmailCronLog(
    'Commandes éligibles : ' . $stats['eligible']
);

foreach ($orders as $order) {
    $orderId = (int) $order['id'];

    orderEmailCronLog(
        'Commande #' . $orderId
        . ' | ' . $order['order_number']
        . ' | Statut : '
        . $order['confirmation_email_status']
    );

    if (!$execute) {
        orderEmailCronLog(
            '  DRY-RUN : aucun e-mail envoyé.'
        );
        continue;
    }

    try {
        $sent = processOrderConfirmationEmail(
            $pdo,
            $orderId
        );

        if ($sent) {
            $stats['sent']++;

            orderEmailCronLog(
                '  OK : e-mail envoyé.'
            );
        } else {
            $stats['not_sent']++;

            orderEmailCronLog(
                '  INFO : e-mail non envoyé '
                . 'ou commande déjà traitée.'
            );
        }
    } catch (Throwable $e) {
        $stats['errors']++;

        orderEmailCronLog(
            '  ERREUR : ' . $e->getMessage()
        );

        error_log(
            '[ORDER EMAIL CRON] Order '
            . $orderId
            . ' | '
            . $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Détection des envois bloqués
|--------------------------------------------------------------------------
*/

$blockedQuery = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE payment_status = 'paid'
      AND confirmation_email_status = 'sending'
      AND confirmation_email_sent_at IS NULL
      AND confirmation_email_started_at
          < NOW() - INTERVAL 30 MINUTE
");

$stats['blocked'] = (int) $blockedQuery->fetchColumn();

if ($stats['blocked'] > 0) {
    orderEmailCronLog(
        'ALERTE : '
        . $stats['blocked']
        . ' e-mail(s) bloqué(s) à sending.'
    );
}

/*
|--------------------------------------------------------------------------
| Résumé
|--------------------------------------------------------------------------
*/

orderEmailCronLog('----------------------------------------');

foreach ($stats as $key => $value) {
    orderEmailCronLog($key . ' : ' . $value);
}

orderEmailCronLog('Réconciliation e-mails terminée.');

exit($stats['errors'] > 0 ? 1 : 0);
