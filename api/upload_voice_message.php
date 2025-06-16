<?php
// api/upload_voice_message.php
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
    if (!isset($_FILES['voice']) || $_FILES['voice']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier vocal reçu ou erreur d\'upload');
    }

    $file = $_FILES['voice'];

    // Vérifications de sécurité
    // Vérifications de sécurité - Types MIME plus complets pour l'audio
    $allowedTypes = [
        'audio/wav',
        'audio/mpeg',
        'audio/mp3',
        'audio/ogg',
        'audio/webm',
        'audio/webm;codecs=opus',
        'audio/ogg;codecs=opus',
        'audio/mp4',
        'audio/x-wav',
        'audio/wave',
        'audio/x-ms-wma'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        // Vérification alternative basée sur l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['wav', 'mp3', 'ogg', 'webm', 'mp4', 'm4a'];

        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Type de fichier non autorisé. Types acceptés: ' . implode(', ', $allowedExtensions));
        }
    }

    // Limite de taille (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Fichier trop volumineux (maximum 5MB)');
    }

    // Créer le dossier s'il n'existe pas
    $uploadDir = '../uploads/voices/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'wav';
    $filename = 'voice_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }

    // Retourner le chemin relatif
    $relativePath = 'uploads/voices/' . $filename;

    echo json_encode([
        'success' => true,
        'voice_path' => $relativePath,
        'filename' => $filename
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
