<?php
// backend/api/auth/login.php
require_once '../../config/config.php';
require_once '../../auth/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $login = sanitize($input['login'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($login) || empty($password)) {
        throw new Exception('Tous les champs sont requis');
    }

    $user = new User();
    $result = $user->login($login, $password);

    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['user_pseudo'] = $result['user']['pseudo'];
        $_SESSION['user_email'] = $result['user']['email'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => $result['user']
        ]);
    } else {
        throw new Exception($result['message']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
