<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
