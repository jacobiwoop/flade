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

    // Vérification du type MIME autorisé
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
    }

    // Limite taille 2MB
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Le fichier est trop volumineux (maximum 2MB)');
    }

    // Upload vers API distante
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
        CURLOPT_SSL_VERIFYPEER => false, // Désactiver temporairement SSL (à sécuriser en prod)
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

    // Mise à jour dans la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Récupérer l’ancienne photo (pour info seulement, pas de suppression locale ici)
    $query = "SELECT profile_photo FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $oldPhoto = $stmt->fetchColumn();

    // Mettre à jour avec le nouveau nom de fichier
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
