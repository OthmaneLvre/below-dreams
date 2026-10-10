<?php

/*
|--------------------------------------------------------------------------
| BELOW DREAMS — CONNEXION BASE DE DONNÉES
|--------------------------------------------------------------------------
|
| Aucun identifiant sensible dans ce fichier.
|
*/

$localConfigPath = __DIR__ . '/database.local.php';
$productionConfigPath = __DIR__ . '/database.production.php';

if (is_file($localConfigPath)) {
    $databaseConfig = require $localConfigPath;
} elseif (is_file($productionConfigPath)) {
    $databaseConfig = require $productionConfigPath;
} else {
    error_log('Below Dreams : configuration MySQL absente.');

    http_response_code(500);
    exit('Configuration de la base de données indisponible.');
}

$host = $databaseConfig['host'];
$dbname = $databaseConfig['dbname'];
$username = $databaseConfig['username'];
$password = $databaseConfig['password'];

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log(
        'Below Dreams - erreur MySQL : '
        . $e->getMessage()
    );

    http_response_code(500);
    exit('Erreur de connexion à la base de données.');
}
