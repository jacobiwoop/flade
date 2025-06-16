<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier uploadé ou erreur d\'upload');
    }

    $file = $_FILES['image'];
    
    // Vérifications
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        throw new Exception('Le fichier est trop volumineux (maximum 5MB)');
    }

    // Créer le dossier s'il n'existe pas
    $uploadDir = '../uploads/messages/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'msg_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }

    // Redimensionner l'image si nécessaire (optionnel)
    resizeImage($filepath, 800, 600);

    echo json_encode([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'image_path' => 'uploads/messages/' . $filename,
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function resizeImage($filepath, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) return;

    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $imageType = $imageInfo[2];

    // Calculer les nouvelles dimensions
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    if ($ratio >= 1) return; // Pas besoin de redimensionner

    $newWidth = round($originalWidth * $ratio);
    $newHeight = round($originalHeight * $ratio);

    // Créer une nouvelle image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Conserver la transparence pour PNG et GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Charger l'image source
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($filepath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }

    // Redimensionner
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // Sauvegarder
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $filepath);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $filepath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($newImage, $filepath, 85);
            break;
    }

    imagedestroy($sourceImage);
    imagedestroy($newImage);
}
?>
