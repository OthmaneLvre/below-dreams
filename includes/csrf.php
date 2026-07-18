<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Retourne le token CSRF courant ou en génère un.
 */
function csrfToken(): string
{
    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Génère le champ HTML à placer dans les formulaires POST.
 */
function csrfField(): string
{
    return sprintf(
        '<input type="hidden" name="csrf_token" value="%s">',
        htmlspecialchars(
            csrfToken(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

/**
 * Vérifie un token CSRF reçu.
 */
function csrfIsValid(?string $submittedToken): bool
{
    $storedToken = $_SESSION['csrf_token'] ?? null;

    if (
        !is_string($submittedToken)
        || $submittedToken === ''
        || !is_string($storedToken)
        || $storedToken === ''
    ) {
        return false;
    }

    return hash_equals(
        $storedToken,
        $submittedToken
    );
}

/**
 * Bloque immédiatement une requête POST invalide.
 */
function requireValidCsrfToken(): void
{
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!csrfIsValid(
        is_string($submittedToken)
            ? $submittedToken
            : null
    )) {
        http_response_code(419);
        exit(
            'Votre session a expiré. '
            . 'Veuillez actualiser la page et réessayer.'
        );
    }
}