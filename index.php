<?php require_once 'config/config.php';
// index.php - Page d'accueil qui redirige vers login ou dashboard


if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    header('Location: login.php');
    exit();
}
?>
