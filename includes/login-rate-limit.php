<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BELOW DREAMS — LIMITATION DES TENTATIVES DE CONNEXION
|--------------------------------------------------------------------------
*/

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;
const LOGIN_ATTEMPT_WINDOW_MINUTES = 15;

/**
 * Normalise puis hache l’identifiant utilisé pour la connexion.
 */
function loginIdentifierHash(string $identifier): string
{
    return hash(
        'sha256',
        mb_strtolower(trim($identifier))
    );
}

/**
 * Retourne l’adresse IP du visiteur.
 *
 * On utilise REMOTE_ADDR comme source de confiance par défaut.
 * Les en-têtes proxy ne doivent être utilisés que si le proxy est maîtrisé.
 */
function loginClientIp(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/**
 * Hache l’adresse IP avant son enregistrement.
 */
function loginIpHash(): string
{
    return hash('sha256', loginClientIp());
}

/**
 * Retourne l’état actuel de la limitation.
 *
 * @return array{
 *     locked: bool,
 *     remaining_seconds: int,
 *     remaining_attempts: int
 * }
 */
function getLoginRateLimitStatus(
    PDO $pdo,
    string $scope,
    string $identifier
): array {
    $identifierHash = loginIdentifierHash($identifier);
    $ipHash = loginIpHash();

    $query = $pdo->prepare("
        SELECT
            failed_attempts,
            first_failed_at,
            locked_until
        FROM login_attempts
        WHERE scope = ?
        AND identifier_hash = ?
        AND ip_hash = ?
        LIMIT 1
    ");

    $query->execute([
        $scope,
        $identifierHash,
        $ipHash
    ]);

    $attempt = $query->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        return [
            'locked' => false,
            'remaining_seconds' => 0,
            'remaining_attempts' => LOGIN_MAX_ATTEMPTS
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Suppression d’une fenêtre ancienne
    |--------------------------------------------------------------------------
    */

    if (!empty($attempt['first_failed_at'])) {
        $firstFailure = new DateTimeImmutable(
            $attempt['first_failed_at']
        );

        $windowLimit = $firstFailure->modify(
            '+' . LOGIN_ATTEMPT_WINDOW_MINUTES . ' minutes'
        );

        if (
            new DateTimeImmutable() > $windowLimit
            && empty($attempt['locked_until'])
        ) {
            clearLoginAttempts(
                $pdo,
                $scope,
                $identifier
            );

            return [
                'locked' => false,
                'remaining_seconds' => 0,
                'remaining_attempts' => LOGIN_MAX_ATTEMPTS
            ];
        }
    }

    if (!empty($attempt['locked_until'])) {
        $lockedUntil = new DateTimeImmutable(
            $attempt['locked_until']
        );

        $now = new DateTimeImmutable();

        if ($lockedUntil > $now) {
            return [
                'locked' => true,
                'remaining_seconds' =>
                    $lockedUntil->getTimestamp()
                    - $now->getTimestamp(),
                'remaining_attempts' => 0
            ];
        }

        clearLoginAttempts(
            $pdo,
            $scope,
            $identifier
        );

        return [
            'locked' => false,
            'remaining_seconds' => 0,
            'remaining_attempts' => LOGIN_MAX_ATTEMPTS
        ];
    }

    $failedAttempts = (int) $attempt['failed_attempts'];

    return [
        'locked' => false,
        'remaining_seconds' => 0,
        'remaining_attempts' => max(
            LOGIN_MAX_ATTEMPTS - $failedAttempts,
            0
        )
    ];
}

/**
 * Enregistre un nouvel échec de connexion.
 */
function recordFailedLoginAttempt(
    PDO $pdo,
    string $scope,
    string $identifier
): void {
    $identifierHash = loginIdentifierHash($identifier);
    $ipHash = loginIpHash();

    $query = $pdo->prepare("
        SELECT
            id,
            failed_attempts,
            first_failed_at,
            locked_until
        FROM login_attempts
        WHERE scope = ?
        AND identifier_hash = ?
        AND ip_hash = ?
        LIMIT 1
    ");

    $query->execute([
        $scope,
        $identifierHash,
        $ipHash
    ]);

    $attempt = $query->fetch(PDO::FETCH_ASSOC);

    $now = new DateTimeImmutable();

    if (!$attempt) {
        $insert = $pdo->prepare("
            INSERT INTO login_attempts (
                scope,
                identifier_hash,
                ip_hash,
                failed_attempts,
                first_failed_at,
                last_failed_at
            )
            VALUES (?, ?, ?, 1, NOW(), NOW())
        ");

        $insert->execute([
            $scope,
            $identifierHash,
            $ipHash
        ]);

        return;
    }

    $firstFailure = !empty($attempt['first_failed_at'])
        ? new DateTimeImmutable($attempt['first_failed_at'])
        : $now;

    $windowLimit = $firstFailure->modify(
        '+' . LOGIN_ATTEMPT_WINDOW_MINUTES . ' minutes'
    );

    /*
    |--------------------------------------------------------------------------
    | Nouvelle fenêtre après expiration
    |--------------------------------------------------------------------------
    */

    if ($now > $windowLimit) {
        $reset = $pdo->prepare("
            UPDATE login_attempts
            SET
                failed_attempts = 1,
                first_failed_at = NOW(),
                last_failed_at = NOW(),
                locked_until = NULL
            WHERE id = ?
        ");

        $reset->execute([
            (int) $attempt['id']
        ]);

        return;
    }

    $newAttemptCount =
        (int) $attempt['failed_attempts'] + 1;

    $lockedUntil = null;

    if ($newAttemptCount >= LOGIN_MAX_ATTEMPTS) {
        $lockedUntil = $now
            ->modify('+' . LOGIN_LOCK_MINUTES . ' minutes')
            ->format('Y-m-d H:i:s');
    }

    $update = $pdo->prepare("
        UPDATE login_attempts
        SET
            failed_attempts = ?,
            last_failed_at = NOW(),
            locked_until = ?
        WHERE id = ?
    ");

    $update->execute([
        $newAttemptCount,
        $lockedUntil,
        (int) $attempt['id']
    ]);
}

/**
 * Supprime les échecs après une connexion réussie
 * ou après expiration de la période de blocage.
 */
function clearLoginAttempts(
    PDO $pdo,
    string $scope,
    string $identifier
): void {
    $delete = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE scope = ?
        AND identifier_hash = ?
        AND ip_hash = ?
    ");

    $delete->execute([
        $scope,
        loginIdentifierHash($identifier),
        loginIpHash()
    ]);
}

/**
 * Formate la durée de blocage pour l’utilisateur.
 */
function formatLoginLockDuration(int $seconds): string
{
    $minutes = max(
        1,
        (int) ceil($seconds / 60)
    );

    return $minutes === 1
        ? '1 minute'
        : $minutes . ' minutes';
}