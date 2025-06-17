<?php
// index.php - Page d'accueil qui redirige vers login ou dashboard
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    require_once 'login.php';
}
?>
