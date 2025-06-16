<?php
require_once '../config/config.php';
require_once '../classes/Chat.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $user_id = $_SESSION['user_id'];
    $last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;

    // Vérifier s'il y a de nouveaux messages
    $query = "SELECT MAX(m.id) as latest_id, COUNT(*) as new_count
              FROM messages m 
              INNER JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id 
              WHERE cp.user_id = ? AND m.id > ? AND m.user_id != ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $last_message_id, $user_id]);
    $result = $stmt->fetch();

    $has_new_messages = $result['new_count'] > 0;
    $latest_id = $result['latest_id'] ?: $last_message_id;

    // Si il y a de nouveaux messages, récupérer les détails du dernier
    $last_message_details = null;
    if ($has_new_messages) {
        $detailQuery = "SELECT m.*, u.pseudo as sender_name, u.profile_photo as sender_photo,
                               c.name as conversation_name
                        FROM messages m
                        INNER JOIN users u ON m.user_id = u.id
                        INNER JOIN conversations c ON m.conversation_id = c.id
                        INNER JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
                        WHERE cp.user_id = ? AND m.user_id != ? AND m.id = ?";

        $detailStmt = $conn->prepare($detailQuery);
        $detailStmt->execute([$user_id, $user_id, $latest_id]);
        $last_message_details = $detailStmt->fetch();
    }

    echo json_encode([
        'success' => true,
        'has_new_messages' => $has_new_messages,
        'last_message_id' => $latest_id,
        'new_count' => intval($result['new_count']),
        'last_message' => $last_message_details
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
