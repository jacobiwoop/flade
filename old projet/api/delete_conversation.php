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

    if (!$input || !isset($input['conversation_id'])) {
        throw new Exception('ID de conversation manquant');
    }

    $conversation_id = intval($input['conversation_id']);

    if ($conversation_id <= 0) {
        throw new Exception('ID de conversation invalide');
    }

    $chat = new Chat();

    // Vérifier que l'utilisateur fait partie de la conversation
    if (!$chat->isUserInConversation($_SESSION['user_id'], $conversation_id)) {
        throw new Exception('Accès non autorisé à cette conversation');
    }

    // Supprimer l'utilisateur de la conversation
    $database = new Database();
    $conn = $database->getConnection();

    $conn->beginTransaction();

    // Supprimer la participation de l'utilisateur
    $query = "DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$conversation_id, $_SESSION['user_id']]);

    // Vérifier s'il reste des participants
    $query = "SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$conversation_id]);
    $participantCount = $stmt->fetchColumn();

    // Si plus de participants, supprimer la conversation entière
    if ($participantCount == 0) {
        $query = "DELETE FROM conversations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$conversation_id]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Conversation supprimée avec succès'
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
