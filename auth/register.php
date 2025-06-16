<?php
// auth/register.php
require_once '../config/config.php';
require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = sanitize($_POST['pseudo']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($pseudo) || empty($email) || empty($password) || empty($confirm_password)) {
        setError('Tous les champs sont requis');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setError('Email invalide');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        setError('Le mot de passe doit contenir au moins 6 caractères');
        redirect('register.php');
    }

    if ($password !== $confirm_password) {
        setError('Les mots de passe ne correspondent pas');
        redirect('register.php');
    }

    $user = new User();
    $result = $user->register($pseudo, $email, $password);

    if ($result['success']) {
        setSuccess($result['message']);
        redirect('login.php');
    } else {
        setError($result['message']);
        redirect('register.php');
    }
}
