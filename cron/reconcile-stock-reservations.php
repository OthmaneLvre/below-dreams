<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Below Dreams — Réconciliation des réservations de stock
|--------------------------------------------------------------------------
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../includes/stock-reservation.php';

/*
|--------------------------------------------------------------------------
| Arguments
|--------------------------------------------------------------------------
*/

$options = array_slice($argv, 1);

if (
    count($options) !== 1
    || !in_array($options[0], ['--dry-run', '--execute'], true)
) {
    fwrite(
        STDERR,
        "Usage: php cron/reconcile-stock-reservations.php "
        . "[--dry-run|--execute]\n"
    );

    exit(1);
}

$execute = $options[0] === '--execute';

/*
|--------------------------------------------------------------------------
| Paramètres
|--------------------------------------------------------------------------
|
| On ignore les réservations très récentes pour laisser
| le temps au checkout d'enregistrer la session Stripe.
|
*/

$minimumAgeMinutes = 10;
$batchSize = 50;

$stats = [
    'checked' => 0,
    'active' => 0,
    'paid' => 0,
    'expired' => 0,
    'released' => 0,
    'missing_session' => 0,
    'errors' => 0
];

function cronLog(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] '
        . $message . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| Recherche des réservations
|--------------------------------------------------------------------------
*/

try {
    $query = $pdo->prepare("
        SELECT
            id,
            order_number,
            stripe_session_id,
            stock_reservation_status,
            payment_status,
            stock_reserved_at
        FROM orders
        WHERE stock_reservation_status = 'reserved'
          AND stock_reserved_at <= DATE_SUB(
              NOW(),
              INTERVAL {$minimumAgeMinutes} MINUTE
          )
        ORDER BY stock_reserved_at ASC, id ASC
        LIMIT {$batchSize}
    ");

    $query->execute();

    $orders = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    cronLog('ERREUR SQL : ' . $e->getMessage());
    exit(1);
}

cronLog(
    'Démarrage réconciliation | Mode : '
    . ($execute ? 'EXECUTE' : 'DRY-RUN')
);

cronLog(
    'Réservations à examiner : ' . count($orders)
);

/*
|--------------------------------------------------------------------------
| Analyse des réservations
|--------------------------------------------------------------------------
*/

foreach ($orders as $order) {

    $stats['checked']++;

    $orderId = (int) $order['id'];
    $orderNumber = $order['order_number'] ?: "#{$orderId}";
    $sessionId = $order['stripe_session_id'];

    cronLog("Commande {$orderNumber}");

    /*
    |--------------------------------------------------------------------------
    | Session Stripe absente
    |--------------------------------------------------------------------------
    */

    if (empty($sessionId)) {
        $stats['missing_session']++;

        cronLog(
            '  ALERTE : aucune session Stripe enregistrée. '
            . 'Réservation conservée.'
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification directe auprès de Stripe
    |--------------------------------------------------------------------------
    */

    try {
        $session = \Stripe\Checkout\Session::retrieve(
            $sessionId
        );
    } catch (Throwable $e) {
        $stats['errors']++;

        cronLog(
            '  ERREUR Stripe : ' . $e->getMessage()
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Contrôle d'appartenance de la session
    |--------------------------------------------------------------------------
    */

    if (
        (string) ($session->metadata->order_id ?? '') !==
        (string) $orderId
        || (string) ($session->client_reference_id ?? '') !==
        (string) $orderId
    ) {
        $stats['errors']++;

        cronLog(
            '  ALERTE : session Stripe incohérente. '
            . 'Aucune modification.'
        );

        continue;
    }

    $stripeStatus = (string) ($session->status ?? '');
    $stripePaymentStatus = (string) (
        $session->payment_status ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | Session payée
    |--------------------------------------------------------------------------
    */

    if ($stripePaymentStatus === 'paid') {
        $stats['paid']++;

        cronLog(
            '  ALERTE : paiement confirmé chez Stripe, '
            . 'mais réservation encore active. '
            . 'Réconciliation du paiement nécessaire.'
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Session ouverte
    |--------------------------------------------------------------------------
    */

    if ($stripeStatus === 'open') {
        $stats['active']++;

        cronLog(
            '  Session encore ouverte. '
            . 'Réservation conservée.'
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Session expirée
    |--------------------------------------------------------------------------
    */

    if (
        $stripeStatus !== 'expired'
        || $stripePaymentStatus !== 'unpaid'
    ) {
        $stats['errors']++;

        cronLog(
            '  ALERTE : état Stripe non pris en charge : '
            . $stripeStatus
            . ' / '
            . $stripePaymentStatus
        );

        continue;
    }

    $stats['expired']++;

    if (!$execute) {
        cronLog(
            '  DRY-RUN : stock à restituer. '
            . 'Aucune modification.'
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Libération transactionnelle
    |--------------------------------------------------------------------------
    */

    try {
        $pdo->beginTransaction();

        $lockQuery = $pdo->prepare("
            SELECT
                id,
                payment_status,
                stock_reservation_status,
                stripe_session_id
            FROM orders
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $lockQuery->execute([$orderId]);

        $lockedOrder = $lockQuery->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$lockedOrder) {
            throw new RuntimeException(
                'Commande introuvable.'
            );
        }

        if (
            $lockedOrder['stock_reservation_status']
            !== 'reserved'
            || $lockedOrder['payment_status'] === 'paid'
        ) {
            $pdo->commit();

            cronLog(
                '  Commande déjà traitée. Aucune modification.'
            );

            continue;
        }

        if (
            $lockedOrder['stripe_session_id'] !== $sessionId
        ) {
            throw new RuntimeException(
                'La session Stripe a changé.'
            );
        }

        $released = releaseOrderStockReservation(
            $pdo,
            $orderId,
            $lockedOrder
        );

        $pdo->commit();

        if ($released) {
            $stats['released']++;

            cronLog(
                '  OK : réservation libérée et stock restitué.'
            );
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $stats['errors']++;

        cronLog(
            '  ERREUR libération : ' . $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Résumé
|--------------------------------------------------------------------------
*/

cronLog('----------------------------------------');

foreach ($stats as $key => $value) {
    cronLog($key . ' : ' . $value);
}

cronLog('Réconciliation terminée.');

exit($stats['errors'] > 0 ? 1 : 0);
