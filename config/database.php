<?php

/* ==========================================================
   BELOW DREAMS — CONNEXION BASE DE DONNÉES
   ========================================================== */

$host = "localhost";
$dbname = "belowdreams";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données.");
}