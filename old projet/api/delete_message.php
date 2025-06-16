<?php
require_once '../config/config.php';
require_once '../classes/Chat.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['message_id'])) {
        throw new Exception('ID de message manquant');
    }

    $message_id = intval($input['message_id']);

    if ($message_id <= 0) {
        throw new Exception('ID de message invalide');
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier que le message appartient à l'utilisateur
    $query = "SELECT user_id, image_path, conversation_id FROM messages WHERE id = ? AND is_deleted = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();

    if (!$message) {
        throw new Exception('Message non trouvé');
    }

    if ($message['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Vous ne pouvez supprimer que vos propres messages');
    }

    // Vérifier que l'utilisateur fait partie de la conversation
    $chat = new Chat();
    if (!$chat->isUserInConversation($_SESSION['user_id'], $message['conversation_id'])) {
        throw new Exception('Accès non autorisé à cette conversation');
    }

    // Supprimer le message (soft delete)
    $query = "UPDATE messages SET is_deleted = TRUE, deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$message_id]);

    // Supprimer le fichier image si il existe
    if ($message['image_path'] && file_exists('../' . $message['image_path'])) {
        unlink('../' . $message['image_path']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Message supprimé avec succès',
        'message_id' => $message_id
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
