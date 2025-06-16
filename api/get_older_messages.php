<?php
// api/get_older_messages.php
require_once '../config/config.php';
require_once '../classes/Chat.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
    $oldest_message_id = isset($_GET['oldest_message_id']) ? intval($_GET['oldest_message_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

    if ($conversation_id <= 0) {
        throw new Exception('ID de conversation invalide');
    }

    // Limiter le nombre de messages pour éviter les abus
    if ($limit > 50) {
        $limit = 50;
    }

    $chat = new Chat();

    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$chat->isUserInConversation($_SESSION['user_id'], $conversation_id)) {
        throw new Exception('Accès non autorisé à cette conversation');
    }

    // Récupérer les messages plus anciens
    $messages = $chat->getOlderMessages($conversation_id, $_SESSION['user_id'], $oldest_message_id, $limit);

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
