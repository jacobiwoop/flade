<?php
// auth/logout.php
require_once '../config/config.php';
require_once 'User.php';

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

// Rediriger vers la page de connexion
redirect('login.php');
