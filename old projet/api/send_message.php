<?php
// api/send_message.php
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

    $conversation_id = intval($input['conversation_id']);
    $content = isset($input['content']) ? trim($input['content']) : '';
    $reply_to_message_id = isset($input['reply_to_message_id']) ? intval($input['reply_to_message_id']) : null;
    $image_path = isset($input['image_path']) ? $input['image_path'] : null;
    // Ajouter le support des messages vocaux
    $voice_path = isset($input['voice_path']) ? $input['voice_path'] : null;

    if ($conversation_id <= 0) {
        throw new Exception('ID de conversation invalide');
    }

    // Vérifier qu'il y a au moins du contenu, une image ou un message vocal
    if (empty($content) && empty($image_path) && empty($voice_path)) {
        throw new Exception('Le message ne peut pas être vide');
    }

    if (!empty($content) && strlen($content) > 1000) {
        throw new Exception('Le message est trop long (maximum 1000 caractères)');
    }

    $chat = new Chat();

    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$chat->isUserInConversation($_SESSION['user_id'], $conversation_id)) {
        throw new Exception('Accès non autorisé à cette conversation');
    }

    // Vérifier que le message de réponse existe et fait partie de la même conversation
    if ($reply_to_message_id) {
        $database = new Database();
        $conn = $database->getConnection();
        $query = "SELECT conversation_id FROM messages WHERE id = ? AND is_deleted = FALSE";
        $stmt = $conn->prepare($query);
        $stmt->execute([$reply_to_message_id]);
        $reply_message = $stmt->fetch();

        if (!$reply_message || $reply_message['conversation_id'] != $conversation_id) {
            throw new Exception('Message de réponse invalide');
        }
    }

    // Envoyer le message avec le chemin vocal
    $message = $chat->sendMessage($conversation_id, $_SESSION['user_id'], $content, $reply_to_message_id, $image_path, $voice_path);

    if (!$message) {
        throw new Exception('Erreur lors de l\'envoi du message');
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
