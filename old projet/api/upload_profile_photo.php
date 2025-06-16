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

    // Créer le dossier s'il n'existe pas
    $uploadDir = '../uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }

    // Mettre à jour la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Supprimer l'ancienne photo si elle existe
    $query = "SELECT profile_photo FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $oldPhoto = $stmt->fetchColumn();

    if ($oldPhoto && file_exists('../uploads/profiles/' . $oldPhoto)) {
        unlink('../uploads/profiles/' . $oldPhoto);
    }

    // Sauvegarder le nouveau chemin
    $query = "UPDATE users SET profile_photo = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$filename, $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Photo de profil mise à jour avec succès',
        'photo_url' => 'uploads/profiles/' . $filename
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
