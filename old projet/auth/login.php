<?php
// auth/login.php
require_once '../config/config.php';
require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitize($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        setError('Tous les champs sont requis');
        redirect('login.php');
    }

    $user = new User();
    $result = $user->login($login, $password);

    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['user_pseudo'] = $result['user']['pseudo'];
        $_SESSION['user_email'] = $result['user']['email'];
        redirect('dashboard.php');
    } else {
        setError($result['message']);
        redirect('login.php');
    }
}
