<?php
// api/mark_message_read.php
require_once '../config/config.php';
require_once '../classes/Chat.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

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
    
    $message_id = isset($input['message_id']) ? intval($input['message_id']) : 0;
    
    if ($message_id <= 0) {
        throw new Exception('ID de message invalide');
    }
    
    $chat = new Chat();
    
    // Marquer le message comme lu
    $result = $chat->markMessageAsRead($message_id, $_SESSION['user_id']);
    
    if ($result) {
        // Récupérer les informations de lecture
        $readers = $chat->getMessageReaders($message_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message marqué comme lu',
            'readers' => $readers
        ]);
    } else {
        throw new Exception('Erreur lors du marquage comme lu');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
