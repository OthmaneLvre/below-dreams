<?php

session_start();

require_once 'config/database.php';
require_once 'config/stripe.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: account/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Acceptations juridiques obligatoires
|--------------------------------------------------------------------------
*/

$acceptCgv = filter_input(
    INPUT_POST,
    'accept_cgv',
    FILTER_VALIDATE_BOOLEAN
);

$acceptPrivacy = filter_input(
    INPUT_POST,
    'accept_privacy',
    FILTER_VALIDATE_BOOLEAN
);

$acceptPaymentObligation = filter_input(
    INPUT_POST,
    'accept_payment_obligation',
    FILTER_VALIDATE_BOOLEAN
);

if (
    !$acceptCgv
    || !$acceptPrivacy
    || !$acceptPaymentObligation
) {
    $_SESSION['checkout_error'] =
        'Vous devez accepter les conditions obligatoires avant de continuer.';

    header('Location: checkout.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Panier et mode de livraison
|--------------------------------------------------------------------------
*/

$cartJson = $_POST['cart'] ?? '';
$cart = json_decode($cartJson, true);

$shippingMethodId = filter_input(
    INPUT_POST,
    'shipping_method_id',
    FILTER_VALIDATE_INT
);

if (empty($cart) || !is_array($cart)) {
    $_SESSION['checkout_error'] =
        'Votre panier est vide ou invalide.';

    header('Location: cart.php');
    exit;
}

if (!$shippingMethodId) {
    $_SESSION['checkout_error'] =
        'Veuillez sélectionner un mode de livraison.';

    header('Location: checkout.php');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Client et adresse
    |--------------------------------------------------------------------------
    */

    $customerQuery = $pdo->prepare("
        SELECT *
        FROM customers
        WHERE id = ?
        LIMIT 1
    ");

    $customerQuery->execute([
        $_SESSION['customer_id']
    ]);

    $customer = $customerQuery->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception('Client introuvable.');
    }

    if (
        empty($customer['shipping_address'])
        || empty($customer['shipping_postcode'])
        || empty($customer['shipping_city'])
        || empty($customer['shipping_country'])
    ) {
        throw new Exception(
            'Veuillez renseigner une adresse de livraison complète.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mode de livraison
    |--------------------------------------------------------------------------
    */

    $shippingQuery = $pdo->prepare("
        SELECT *
        FROM shipping_methods
        WHERE id = ?
        AND is_active = 1
        LIMIT 1
    ");

    $shippingQuery->execute([
        $shippingMethodId
    ]);

    $shippingMethod = $shippingQuery->fetch(PDO::FETCH_ASSOC);

    if (!$shippingMethod) {
        throw new Exception(
            'Mode de livraison indisponible.'
        );
    }

    $pdo->beginTransaction();

    $subtotal = 0.0;
    $lineItems = [];
    $validatedItems = [];

    /*
    |--------------------------------------------------------------------------
    | Validation des produits depuis la base
    |--------------------------------------------------------------------------
    */

    $productQuery = $pdo->prepare("
        SELECT
            id,
            name,
            price,
            image,
            stock,
            status,
            is_active
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    foreach ($cart as $item) {
        $productId = filter_var(
            $item['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $item['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        $size = trim(
            (string) ($item['size'] ?? '')
        );

        if (
            !$productId
            || !$quantity
            || $quantity < 1
            || $size === ''
        ) {
            throw new Exception(
                'Un article du panier est invalide.'
            );
        }

        $productQuery->execute([
            $productId
        ]);

        $product = $productQuery->fetch(PDO::FETCH_ASSOC);

        if (
            !$product
            || (int) $product['is_active'] !== 1
        ) {
            throw new Exception(
                'Un produit de votre panier n’est plus disponible.'
            );
        }

        if (
            $product['status'] === 'stock'
            && (int) $product['stock'] < $quantity
        ) {
            throw new Exception(
                'Le stock disponible est insuffisant pour '
                . $product['name']
                . '.'
            );
        }

        $productPrice = (float) $product['price'];
        $itemTotal = $productPrice * $quantity;

        $subtotal += $itemTotal;

        $validatedItems[] = [
            'product_id' => (int) $product['id'],
            'name' => $product['name'],
            'image' => $product['image'] ?? null,
            'size' => $size,
            'quantity' => $quantity,
            'price' => $productPrice
        ];

        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',

                'product_data' => [
                    'name' => $product['name']
                        . ' - Taille '
                        . $size,
                ],

                'unit_amount' => (int) round(
                    $productPrice * 100
                ),
            ],

            'quantity' => $quantity,
        ];
    }

    if (empty($validatedItems) || $subtotal <= 0) {
        throw new Exception(
            'Votre panier ne contient aucun article valide.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calcul sécurisé des frais de livraison
    |--------------------------------------------------------------------------
    */

    $normalShippingPrice =
        (float) $shippingMethod['price'];

    $shippingPrice = $normalShippingPrice;

    $freeShippingThreshold =
        $shippingMethod['free_shipping_threshold'];

    if (
        $freeShippingThreshold !== null
        && $freeShippingThreshold !== ''
        && $subtotal >= (float) $freeShippingThreshold
    ) {
        $shippingPrice = 0.0;
    }

    $total = $subtotal + $shippingPrice;

    if ($shippingPrice > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',

                'product_data' => [
                    'name' => 'Livraison - '
                        . $shippingMethod['name'],
                ],

                'unit_amount' => (int) round(
                    $shippingPrice * 100
                ),
            ],

            'quantity' => 1,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Copie de l’adresse au moment de la commande
    |--------------------------------------------------------------------------
    */

    $shippingAddressSnapshot = json_encode(
        [
            'firstname' =>
                $customer['firstname'] ?? '',

            'lastname' =>
                $customer['lastname'] ?? '',

            'address' =>
                $customer['shipping_address'] ?? '',

            'postcode' =>
                $customer['shipping_postcode'] ?? '',

            'city' =>
                $customer['shipping_city'] ?? '',

            'country' =>
                $customer['shipping_country'] ?? '',

            'phone' =>
                $customer['phone'] ?? ''
        ],
        JSON_UNESCAPED_UNICODE
    );

    if ($shippingAddressSnapshot === false) {
        throw new Exception(
            'Impossible d’enregistrer l’adresse de livraison.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Création de la commande
    |--------------------------------------------------------------------------
    */

    $orderQuery = $pdo->prepare("
        INSERT INTO orders (
            customer_id,
            subtotal,
            shipping_method_id,
            shipping_method_name,
            shipping_carrier,
            shipping_type,
            shipping_price,
            shipping_address_snapshot,
            total,
            status,
            payment_status
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'pending',
            'unpaid'
        )
    ");

    $orderQuery->execute([
        $_SESSION['customer_id'],
        $subtotal,
        (int) $shippingMethod['id'],
        $shippingMethod['name'],
        $shippingMethod['carrier'] ?? null,
        $shippingMethod['delivery_type'],
        $shippingPrice,
        $shippingAddressSnapshot,
        $total
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $orderNumber = 'BD-'
        . date('Y')
        . str_pad(
            (string) $orderId,
            5,
            '0',
            STR_PAD_LEFT
        );

    $updateOrderNumber = $pdo->prepare("
        UPDATE orders
        SET order_number = ?
        WHERE id = ?
    ");

    $updateOrderNumber->execute([
        $orderNumber,
        $orderId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Enregistrement des articles
    |--------------------------------------------------------------------------
    */

    $itemQuery = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            product_image,
            size,
            quantity,
            price
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($validatedItems as $item) {
        $itemQuery->execute([
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['image'],
            $item['size'],
            $item['quantity'],
            $item['price']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Session Stripe
    |--------------------------------------------------------------------------
    */

    $domain = 'https://belowdreams.com';
    $legalAcceptedAt = gmdate('c');

    $checkoutSession =
        \Stripe\Checkout\Session::create([
            'payment_method_types' => [
                'card'
            ],

            'mode' => 'payment',

            'client_reference_id' =>
                (string) $orderId,

            'customer_email' =>
                $customer['email'],

            'line_items' =>
                $lineItems,

            'success_url' =>
                $domain
                . '/success.php'
                . '?session_id={CHECKOUT_SESSION_ID}',

            'cancel_url' =>
                $domain
                . '/checkout.php'
                . '?payment=cancelled',

            'metadata' => [
                'order_id' =>
                    (string) $orderId,

                'order_number' =>
                    $orderNumber,

                'shipping_method_id' =>
                    (string) $shippingMethod['id'],

                'cgv_accepted' =>
                    '1',

                'privacy_acknowledged' =>
                    '1',

                'payment_obligation_accepted' =>
                    '1',

                'legal_accepted_at' =>
                    $legalAcceptedAt,

                'cgv_version' =>
                    '2026-07-18'
            ],
        ]);

    $updateStripeSession = $pdo->prepare("
        UPDATE orders
        SET stripe_session_id = ?
        WHERE id = ?
    ");

    $updateStripeSession->execute([
        $checkoutSession->id,
        $orderId
    ]);

    $pdo->commit();

    header(
        'Location: ' . $checkoutSession->url
    );

    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Erreur checkout Below Dreams : '
        . $e->getMessage()
    );

    $_SESSION['checkout_error'] =
        $e->getMessage();

    header('Location: checkout.php');
    exit;
}