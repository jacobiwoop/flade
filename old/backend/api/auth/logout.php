<?php
// backend/api/auth/logout.php
require_once '../../config/config.php';
require_once '../../auth/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (isLoggedIn()) {
    try {
        $user = new User();
        // Mettre à jour le statut hors ligne avant de détruire la session
        $user->updateOnlineStatus($_SESSION['user_id'], false);
    } catch (Exception $e) {
        // Log l'erreur mais continue la déconnexion
        error_log("Erreur lors de la mise à jour du statut offline: " . $e->getMessage());
    }
}

// Détruire la session
session_destroy();

echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
?>
