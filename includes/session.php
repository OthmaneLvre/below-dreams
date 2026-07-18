<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BELOW DREAMS — SESSION SÉCURISÉE
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
        )
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');

    session_name('belowdreams_session');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    /*
    |--------------------------------------------------------------------------
    | Renouvellement périodique de l’identifiant de session
    |--------------------------------------------------------------------------
    */

    $regenerationDelay = 30 * 60;

    if (!isset($_SESSION['session_regenerated_at'])) {
        $_SESSION['session_regenerated_at'] = time();
    } elseif (
        time() - (int) $_SESSION['session_regenerated_at']
        >= $regenerationDelay
    ) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated_at'] = time();
    }
}

/**
 * Renouvelle la session après une authentification réussie.
 */
function regenerateSession(): void
{
    session_regenerate_id(true);
    $_SESSION['session_regenerated_at'] = time();
}