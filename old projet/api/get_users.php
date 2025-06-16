<?php
// api/get_users.php
require_once '../config/config.php';
require_once '../auth/User.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Récupérer tous les utilisateurs sauf l'utilisateur actuel avec leurs photos
    $query = "SELECT id, pseudo, email, profile_photo, is_online, last_seen FROM users WHERE id != ? ORDER BY pseudo ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
