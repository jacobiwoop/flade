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

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Le fichier est trop volumineux (maximum 5MB)');
    }

    // Appel à l'API distante
    $apiUrl = 'http://flad.x10.mx/api/upload.php';
    $type = 'message'; // ici on force le type (tu peux rendre ça dynamique)

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'type' => $type
        ],
        CURLOPT_SSL_VERIFYPEER => false,
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

    echo json_encode([
        'success' => true,
        'message' => 'Image uploadée via l’API avec succès',
        'image_path' => $data['url'] ?? null,
        'filename' => basename($data['url'] ?? '')
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
