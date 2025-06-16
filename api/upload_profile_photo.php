<?php
require_once '../config/config.php';
require_once '../auth/User.php';

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
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier uploadé ou erreur d\'upload');
    }

    $file = $_FILES['profile_photo'];

    // Vérifications
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB max
        throw new Exception('Le fichier est trop volumineux (maximum 2MB)');
    }

    // Appel à l'API distante
    $apiUrl = 'https://flad.x10.mx/api/upload.php';
    $type = 'profile';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'type' => $type
        ],
        CURLOPT_SSL_VERIFYPEER => false, // ⚠️ Pour corriger les erreurs SSL
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

    // Mettre à jour la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Supprimer l'ancienne photo si elle existe
    $query = "SELECT profile_photo FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $oldPhoto = $stmt->fetchColumn();

    // ❗ Important : comme l'image est distante, on ne supprime rien ici localement

    // Sauvegarder le nouveau chemin dans la base (tu peux aussi stocker l'URL complète)
    $query = "UPDATE users SET profile_photo = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$filename, $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Photo de profil mise à jour avec succès',
        'photo_url' => $uploadedUrl,
        'filename' => $filename
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
