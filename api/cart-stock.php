<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'error' => 'Méthode non autorisée.',
    ]);
}

$rawBody = file_get_contents('php://input');

if ($rawBody === false || strlen($rawBody) > 16384) {
    respond(400, [
        'success' => false,
        'error' => 'Requête invalide.',
    ]);
}

$data = json_decode($rawBody, true);

if (!is_array($data) || !isset($data['product_ids'])
    || !is_array($data['product_ids'])) {
    respond(400, [
        'success' => false,
        'error' => 'Liste de produits invalide.',
    ]);
}

$ids = $data['product_ids'];

if (count($ids) > 50) {
    respond(400, [
        'success' => false,
        'error' => 'Trop de produits demandés.',
    ]);
}

$productIds = [];

foreach ($ids as $id) {
    if (!is_int($id) || $id < 1) {
        respond(400, [
            'success' => false,
            'error' => 'Identifiant de produit invalide.',
        ]);
    }

    $productIds[] = $id;
}

$productIds = array_values(array_unique($productIds));

if ($productIds === []) {
    respond(200, [
        'success' => true,
        'products' => [],
    ]);
}

try {
    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );

    $query = $pdo->prepare("
        SELECT id, price, stock, status, is_active
        FROM products
        WHERE id IN ($placeholders)
    ");

    $query->execute($productIds);

    $products = [];

    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $id = (int) $product['id'];
        $active = (int) $product['is_active'] === 1
            && $product['status'] === 'stock';

        $products[(string) $id] = [
            'id' => $id,
            'price' => (float) $product['price'],
            'stock' => $active
                ? max(0, (int) $product['stock'])
                : 0,
            'available' => $active,
        ];
    }

    respond(200, [
        'success' => true,
        'products' => $products,
    ]);
} catch (Throwable $e) {
    error_log('Cart stock API: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'error' => 'Impossible de vérifier les stocks.',
    ]);
}
