<?php
// index.php - Page d'accueil qui redirige vers login ou dashboard
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    header('Location: login.php');
    exit();
}
?>
