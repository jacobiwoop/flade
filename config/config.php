<?php session_start();
// config/config.php


// Configuration générale
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/");
define('WEBSOCKET_HOST', 'localhost');
define('WEBSOCKET_PORT', 8080);

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger
function redirect($path)
{
    header("Location: " . BASE_URL . $path);
    exit();
}

// Fonction pour sécuriser les données
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Gestion des erreurs
function setError($message)
{
    $_SESSION['error'] = $message;
}

function getError()
{
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        return $error;
    }
    return null;
}

// Messages de succès
function setSuccess($message)
{
    $_SESSION['success'] = $message;
}

function getSuccess()
{
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
        return $success;
    }
    return null;
}
