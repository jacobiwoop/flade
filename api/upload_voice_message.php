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

    // Vérification MIME
    $allowedTypes = [
        'audio/wav',
        'audio/mpeg',
        'audio/mp3',
        'audio/ogg',
        'audio/webm',
        'audio/mp4',
        'audio/x-wav',
        'audio/wave',
        'audio/x-ms-wma'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        // Vérification extension alternative
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['wav', 'mp3', 'ogg', 'webm', 'mp4', 'm4a'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Type de fichier non autorisé. Types acceptés: ' . implode(', ', $allowedExtensions));
        }
    }

    // Limite taille 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Fichier trop volumineux (maximum 5MB)');
    }

    // Appel à l'API distante pour upload
    $apiUrl = 'https://flad.x10.mx/api/upload.php';
    $type = 'voice';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'type' => $type
        ],
        CURLOPT_SSL_VERIFYPEER => false,  // désactive la vérif SSL, à sécuriser en prod
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        throw new Exception('Erreur cURL : ' . curl_error($curl));
    }

    curl_close($curl);

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !$data || empty($data['success'])) {
        throw new Exception($data['error'] ?? 'Erreur API distante');
    }

    $uploadedUrl = $data['url'] ?? null;
    $filename = basename($uploadedUrl);

    if (!$uploadedUrl) {
        throw new Exception('L\'API n\'a pas retourné d\'URL');
    }

    // Retour succès avec url complète et nom de fichier
    echo json_encode([
        'success' => true,
        'message' => 'Fichier vocal uploadé avec succès via l\'API',
        'voice_url' => $uploadedUrl,
        'filename' => $filename
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
