<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

$name = '';
$carrier = '';
$description = '';
$deliveryType = 'home';
$price = '0.00';
$freeShippingThreshold = '';
$estimatedDelay = '';
$sortOrder = 0;
$isActive = 1;

$allowedDeliveryTypes = [
    'home',
    'relay',
    'pickup',
    'other'
];

function uploadShippingLogo(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Une erreur est survenue pendant l’envoi du logo.'
        );
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException(
            'Le logo ne doit pas dépasser 2 Mo.'
        );
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException(
            'Le logo doit être au format JPG, PNG ou WebP.'
        );
    }

    $uploadDirectory = dirname(__DIR__) . '/uploads/shipping/';

    if (
        !is_dir($uploadDirectory)
        && !mkdir($uploadDirectory, 0755, true)
    ) {
        throw new RuntimeException(
            'Le dossier des logos ne peut pas être créé.'
        );
    }

    $extension = $allowedMimeTypes[$mimeType];

    $filename = 'shipping-'
        . bin2hex(random_bytes(16))
        . '.'
        . $extension;

    $destination = $uploadDirectory . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Le logo n’a pas pu être enregistré.'
        );
    }

    return 'uploads/shipping/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $carrier = trim($_POST['carrier'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deliveryType = trim($_POST['delivery_type'] ?? 'home');

    $price = str_replace(
        ',',
        '.',
        trim($_POST['price'] ?? '0')
    );

    $freeShippingThreshold = str_replace(
        ',',
        '.',
        trim($_POST['free_shipping_threshold'] ?? '')
    );

    $estimatedDelay = trim($_POST['estimated_delay'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $error = 'Le nom du mode de livraison est obligatoire.';
    } elseif (!in_array($deliveryType, $allowedDeliveryTypes, true)) {
        $error = 'Le type de livraison sélectionné est invalide.';
    } elseif (!is_numeric($price) || (float) $price < 0) {
        $error = 'Le prix doit être positif ou égal à zéro.';
    } elseif (
        $freeShippingThreshold !== ''
        && (
            !is_numeric($freeShippingThreshold)
            || (float) $freeShippingThreshold < 0
        )
    ) {
        $error = 'Le seuil de gratuité doit être positif.';
    } elseif ($sortOrder < 0) {
        $error = 'L’ordre d’affichage ne peut pas être négatif.';
    } else {
        $freeShippingThresholdValue = $freeShippingThreshold === ''
            ? null
            : (float) $freeShippingThreshold;

        $logoPath = '';

        try {
            $logoPath = uploadShippingLogo($_FILES['logo'] ?? []);

            $query = $pdo->prepare("
                INSERT INTO shipping_methods (
                    name,
                    carrier,
                    logo,
                    description,
                    delivery_type,
                    price,
                    free_shipping_threshold,
                    estimated_delay,
                    is_active,
                    sort_order
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $query->execute([
                $name,
                $carrier !== '' ? $carrier : null,
                $logoPath !== '' ? $logoPath : null,
                $description !== '' ? $description : null,
                $deliveryType,
                (float) $price,
                $freeShippingThresholdValue,
                $estimatedDelay !== '' ? $estimatedDelay : null,
                $isActive,
                $sortOrder
            ]);

            header('Location: shipping.php?created=1');
            exit;

        } catch (RuntimeException $e) {
            $error = $e->getMessage();

        } catch (PDOException $e) {
            if ($logoPath !== '') {
                $uploadedFile = dirname(__DIR__) . '/' . $logoPath;

                if (is_file($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }

            $error = 'Une erreur est survenue pendant l’ajout.';
        }
    }
}

require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="admin-main">

    <header class="admin-header admin-header-between">

        <div>
            <a href="shipping.php" class="admin-back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la livraison
            </a>

            <h1>Ajouter un mode de livraison</h1>

            <p>
                Configure un transporteur, son tarif et ses conditions
                d’affichage pendant la commande.
            </p>
        </div>

        <a href="shipping.php" class="admin-btn-secondary">
            Annuler
        </a>

    </header>

    <?php if ($error !== '') : ?>
        <div class="admin-alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <section class="admin-section">

        <form
            method="POST"
            enctype="multipart/form-data"
            class="admin-form"
        >

            <div class="form-grid">

                <div class="form-group">
                    <label for="name">
                        Nom du mode de livraison *
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($name) ?>"
                        placeholder="Exemple : Colissimo domicile"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="carrier">
                        Transporteur
                    </label>

                    <input
                        type="text"
                        id="carrier"
                        name="carrier"
                        value="<?= htmlspecialchars($carrier) ?>"
                        placeholder="Exemple : La Poste"
                        maxlength="100"
                    >
                </div>

                <div class="form-group">
                    <label for="logo">
                        Logo du transporteur
                    </label>

                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="form-help">
                        JPG, PNG ou WebP. Taille maximale : 2 Mo.
                    </small>
                </div>

                <div class="form-group">
                    <label for="delivery_type">
                        Type de livraison *
                    </label>

                    <select
                        id="delivery_type"
                        name="delivery_type"
                        required
                    >
                        <option
                            value="home"
                            <?= $deliveryType === 'home' ? 'selected' : '' ?>
                        >
                            Livraison à domicile
                        </option>

                        <option
                            value="relay"
                            <?= $deliveryType === 'relay' ? 'selected' : '' ?>
                        >
                            Livraison en point relais
                        </option>

                        <option
                            value="pickup"
                            <?= $deliveryType === 'pickup' ? 'selected' : '' ?>
                        >
                            Retrait sur place
                        </option>

                        <option
                            value="other"
                            <?= $deliveryType === 'other' ? 'selected' : '' ?>
                        >
                            Autre
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estimated_delay">
                        Délai estimé
                    </label>

                    <input
                        type="text"
                        id="estimated_delay"
                        name="estimated_delay"
                        value="<?= htmlspecialchars($estimatedDelay) ?>"
                        placeholder="Exemple : 2 à 3 jours ouvrés"
                        maxlength="100"
                    >
                </div>

                <div class="form-group">
                    <label for="price">
                        Prix de livraison *
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="<?= htmlspecialchars((string) $price) ?>"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        required
                    >

                    <small class="form-help">
                        Saisis 0 pour une livraison toujours gratuite.
                    </small>
                </div>

                <div class="form-group">
                    <label for="free_shipping_threshold">
                        Livraison offerte à partir de
                    </label>

                    <input
                        type="number"
                        id="free_shipping_threshold"
                        name="free_shipping_threshold"
                        value="<?= htmlspecialchars(
                            (string) $freeShippingThreshold
                        ) ?>"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="Exemple : 100.00"
                    >

                    <small class="form-help">
                        Laisse vide si aucun seuil ne s’applique.
                    </small>
                </div>

                <div class="form-group">
                    <label for="sort_order">
                        Ordre d’affichage
                    </label>

                    <input
                        type="number"
                        id="sort_order"
                        name="sort_order"
                        value="<?= (int) $sortOrder ?>"
                        min="0"
                        step="1"
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="255"
                    placeholder="Exemple : Livraison suivie directement à votre domicile."
                ><?= htmlspecialchars($description) ?></textarea>
            </div>

            <label class="checkbox-group">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?= $isActive === 1 ? 'checked' : '' ?>
                >

                Activer ce mode de livraison
            </label>

            <p class="form-help">
                Seuls les modes actifs seront proposés aux clients.
            </p>

            <div class="form-actions">

                <a href="shipping.php" class="admin-btn-secondary">
                    Annuler
                </a>

                <button type="submit" class="admin-btn admin-submit-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Enregistrer le mode de livraison
                </button>

            </div>

        </form>

    </section>

</main>

<?php require_once 'partials/footer.php'; ?>