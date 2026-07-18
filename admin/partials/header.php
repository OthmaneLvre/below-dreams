<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security-headers.php';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Administration | Below Dreams' ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/admin-responsive.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

</head>
<body>

<div class="admin-layout">