<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BELOW DREAMS — TRAITEMENT DES IMAGES PRODUIT
|--------------------------------------------------------------------------
*/

const PRODUCT_IMAGE_MAX_FILE_SIZE = 10 * 1024 * 1024;

const PRODUCT_IMAGE_MAIN_SIZE = 1400;
const PRODUCT_IMAGE_MEDIUM_SIZE = 800;
const PRODUCT_IMAGE_THUMB_SIZE = 400;

const PRODUCT_IMAGE_WEBP_QUALITY = 82;

/**
 * Traite une image envoyée depuis un formulaire produit.
 *
 * @return array{
 *     image: string,
 *     thumbnail: string
 * }
 */
function processProductImage(
    array $uploadedFile,
    string $productSlug
): array {
    if (!extension_loaded('gd')) {
        throw new RuntimeException(
            "L'extension PHP GD n'est pas activée."
        );
    }

    if (
        !isset(
            $uploadedFile['error'],
            $uploadedFile['tmp_name'],
            $uploadedFile['size']
        )
    ) {
        throw new RuntimeException(
            "Le fichier envoyé est invalide."
        );
    }

    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            getUploadErrorMessage(
                (int) $uploadedFile['error']
            )
        );
    }

    if (
        (int) $uploadedFile['size'] <= 0
        || (int) $uploadedFile['size']
            > PRODUCT_IMAGE_MAX_FILE_SIZE
    ) {
        throw new RuntimeException(
            "L'image ne doit pas dépasser 10 Mo."
        );
    }

    $temporaryPath = (string) $uploadedFile['tmp_name'];

    if (!is_uploaded_file($temporaryPath)) {
        throw new RuntimeException(
            "Le fichier envoyé n'est pas valide."
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temporaryPath);

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException(
            "Seules les images JPG, PNG et WebP sont acceptées."
        );
    }

    $imageInformation = getimagesize($temporaryPath);

    if ($imageInformation === false) {
        throw new RuntimeException(
            "Impossible de lire les dimensions de l'image."
        );
    }

    [$originalWidth, $originalHeight] = $imageInformation;

    if ($originalWidth < 1 || $originalHeight < 1) {
        throw new RuntimeException(
            "Les dimensions de l'image sont invalides."
        );
    }

    $sourceImage = createImageResource(
        $temporaryPath,
        $mimeType
    );

    if (!$sourceImage instanceof GdImage) {
        throw new RuntimeException(
            "Impossible de traiter l'image."
        );
    }

    if ($mimeType === 'image/jpeg') {
        $sourceImage = correctJpegOrientation(
            $sourceImage,
            $temporaryPath
        );
    }

    $safeSlug = sanitizeImageFilename($productSlug);
    $uniqueSuffix = bin2hex(random_bytes(6));

    $baseFilename = $safeSlug . '-' . $uniqueSuffix;

    $projectRoot = dirname(__DIR__);

    $uploadDirectory =
        $projectRoot . '/assets/uploads/products';

    if (
        !is_dir($uploadDirectory)
        && !mkdir($uploadDirectory, 0755, true)
        && !is_dir($uploadDirectory)
    ) {
        imagedestroy($sourceImage);

        throw new RuntimeException(
            "Impossible de créer le dossier des images."
        );
    }

    $mainFilename = $baseFilename . '.webp';
    $mediumFilename = $baseFilename . '-medium.webp';
    $thumbnailFilename = $baseFilename . '-thumb.webp';

    $mainAbsolutePath =
        $uploadDirectory . '/' . $mainFilename;

    $mediumAbsolutePath =
        $uploadDirectory . '/' . $mediumFilename;

    $thumbnailAbsolutePath =
        $uploadDirectory . '/' . $thumbnailFilename;

    resizeAndSaveWebp(
        $sourceImage,
        imagesx($sourceImage),
        imagesy($sourceImage),
        $mainAbsolutePath,
        PRODUCT_IMAGE_MAIN_SIZE,
        PRODUCT_IMAGE_WEBP_QUALITY
    );

    resizeAndSaveWebp(
        $sourceImage,
        imagesx($sourceImage),
        imagesy($sourceImage),
        $mediumAbsolutePath,
        PRODUCT_IMAGE_MEDIUM_SIZE,
        80
    );

    resizeAndSaveWebp(
        $sourceImage,
        imagesx($sourceImage),
        imagesy($sourceImage),
        $thumbnailAbsolutePath,
        PRODUCT_IMAGE_THUMB_SIZE,
        76
    );

    imagedestroy($sourceImage);

    return [
        'image' =>
            'assets/uploads/products/' . $mainFilename,

        'medium' =>
            'assets/uploads/products/' . $mediumFilename,

        'thumbnail' =>
            'assets/uploads/products/' . $thumbnailFilename,
    ];
}

/**
 * Crée une ressource GD depuis le type MIME réel.
 */
function createImageResource(
    string $path,
    string $mimeType
): GdImage {
    $image = match ($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png' => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),

        default => false,
    };

    if (!$image instanceof GdImage) {
        throw new RuntimeException(
            "Le format de l'image ne peut pas être traité."
        );
    }

    return $image;
}

/**
 * Redimensionne une image sans l'agrandir puis l'enregistre en WebP.
 */
function resizeAndSaveWebp(
    GdImage $sourceImage,
    int $sourceWidth,
    int $sourceHeight,
    string $destinationPath,
    int $maximumDimension,
    int $quality
): void {
    $scale = min(
        $maximumDimension / $sourceWidth,
        $maximumDimension / $sourceHeight,
        1
    );

    $destinationWidth = max(
        1,
        (int) round($sourceWidth * $scale)
    );

    $destinationHeight = max(
        1,
        (int) round($sourceHeight * $scale)
    );

    $destinationImage = imagecreatetruecolor(
        $destinationWidth,
        $destinationHeight
    );

    if (!$destinationImage instanceof GdImage) {
        throw new RuntimeException(
            "Impossible de créer l'image redimensionnée."
        );
    }

    imagealphablending($destinationImage, false);
    imagesavealpha($destinationImage, true);

    $transparent = imagecolorallocatealpha(
        $destinationImage,
        0,
        0,
        0,
        127
    );

    imagefill(
        $destinationImage,
        0,
        0,
        $transparent
    );

    $resampled = imagecopyresampled(
        $destinationImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $destinationWidth,
        $destinationHeight,
        $sourceWidth,
        $sourceHeight
    );

    if (!$resampled) {
        imagedestroy($destinationImage);

        throw new RuntimeException(
            "Impossible de redimensionner l'image."
        );
    }

    if (
        !imagewebp(
            $destinationImage,
            $destinationPath,
            $quality
        )
    ) {
        imagedestroy($destinationImage);

        throw new RuntimeException(
            "Impossible d'enregistrer l'image WebP."
        );
    }

    imagedestroy($destinationImage);
}

/**
 * Corrige l'orientation EXIF des photographies JPEG.
 */
function correctJpegOrientation(
    GdImage $image,
    string $path
): GdImage {
    if (!function_exists('exif_read_data')) {
        return $image;
    }

    $exif = @exif_read_data($path);

    $orientation = is_array($exif)
        ? (int) ($exif['Orientation'] ?? 1)
        : 1;

    $rotatedImage = match ($orientation) {
        3 => imagerotate($image, 180, 0),
        6 => imagerotate($image, -90, 0),
        8 => imagerotate($image, 90, 0),
        default => $image,
    };

    if (
        $rotatedImage instanceof GdImage
        && $rotatedImage !== $image
    ) {
        imagedestroy($image);

        return $rotatedImage;
    }

    return $image;
}

/**
 * Génère un nom de fichier sûr.
 */
function sanitizeImageFilename(string $value): string
{
    $value = trim(mb_strtolower($value));

    $value = iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $value
    ) ?: $value;

    $value = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $value
    ) ?? '';

    $value = trim($value, '-');

    return $value !== ''
        ? $value
        : 'produit';
}

/**
 * Retourne un message lisible pour les erreurs d'upload PHP.
 */
function getUploadErrorMessage(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE =>
            "L'image envoyée est trop volumineuse.",

        UPLOAD_ERR_PARTIAL =>
            "Le téléchargement de l'image est incomplet.",

        UPLOAD_ERR_NO_FILE =>
            "Veuillez sélectionner une image.",

        UPLOAD_ERR_NO_TMP_DIR =>
            "Le dossier temporaire du serveur est indisponible.",

        UPLOAD_ERR_CANT_WRITE =>
            "Le serveur ne peut pas enregistrer l'image.",

        UPLOAD_ERR_EXTENSION =>
            "Le téléchargement a été bloqué par le serveur.",

        default =>
            "Une erreur est survenue pendant le téléchargement.",
    };
}

/**
 * Supprime une ancienne image produit en vérifiant son emplacement.
 */
function deleteProductImage(?string $relativePath): void
{
    if (
        $relativePath === null
        || $relativePath === ''
    ) {
        return;
    }

    $projectRoot = realpath(dirname(__DIR__));

    $uploadDirectory = realpath(
        dirname(__DIR__)
        . '/assets/uploads/products'
    );

    $absolutePath = realpath(
        dirname(__DIR__) . '/' . ltrim(
            $relativePath,
            '/'
        )
    );

    if (
        $projectRoot === false
        || $uploadDirectory === false
        || $absolutePath === false
        || !str_starts_with(
            $absolutePath,
            $uploadDirectory
                . DIRECTORY_SEPARATOR
        )
    ) {
        return;
    }

    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
}

/**
 * Retourne le chemin d'une variante générée à partir du chemin principal.
 */
function productImageVariantPath(
    ?string $mainImagePath,
    string $variant
): ?string {
    if ($mainImagePath === null || $mainImagePath === '') {
        return null;
    }

    /*
     * Les anciennes images JPG/PNG ne disposent pas encore
     * de variantes automatiques.
     */
    if (
        !str_starts_with(
            $mainImagePath,
            'assets/uploads/products/'
        )
        || !str_ends_with(
            mb_strtolower($mainImagePath),
            '.webp'
        )
    ) {
        return $mainImagePath;
    }

    $suffix = match ($variant) {
        'medium' => '-medium.webp',
        'thumbnail', 'thumb' => '-thumb.webp',
        default => '.webp',
    };

    if ($suffix === '.webp') {
        return $mainImagePath;
    }

    return substr($mainImagePath, 0, -5) . $suffix;
}

/**
 * Supprime l'image principale et toutes ses variantes.
 */
function deleteProductImageSet(?string $mainImagePath): void
{
    if ($mainImagePath === null || $mainImagePath === '') {
        return;
    }

    $paths = array_unique([
        $mainImagePath,
        productImageVariantPath($mainImagePath, 'medium'),
        productImageVariantPath($mainImagePath, 'thumbnail'),
    ]);

    foreach ($paths as $path) {
        deleteProductImage($path);
    }
}

/**
 * Transforme une entrée d'un upload multiple en fichier PHP standard.
 */
function normalizeUploadedFile(
    array $files,
    int $index
): array {
    return [
        'name' => $files['name'][$index] ?? '',
        'type' => $files['type'][$index] ?? '',
        'tmp_name' => $files['tmp_name'][$index] ?? '',
        'error' => $files['error'][$index]
            ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$index] ?? 0,
    ];
}