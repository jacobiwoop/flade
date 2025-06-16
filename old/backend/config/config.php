<?php
// backend/config/config.php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'floade_chat');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration générale
define('SITE_URL', 'http://localhost/floade');
define('UPLOAD_PATH', '../frontend/uploads/');

// Inclure la classe Database
require_once 'database.php';

// Fonctions utilitaires
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function setError($message) {
    $_SESSION['error'] = $message;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function getError() {
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        return $error;
    }
    return null;
}

function getSuccess() {
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
        return $success;
    }
    return null;
}
?>
