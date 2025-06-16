<?php
// api/create_direct_conversation.php
require_once '../config/config.php';
require_once '../classes/Chat.php';
require_once '../auth/User.php';

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

    if (!$input || !isset($input['user_id'])) {
        throw new Exception('ID utilisateur manquant');
    }

    $other_user_id = intval($input['user_id']);
    $current_user_id = $_SESSION['user_id'];

    if ($other_user_id <= 0) {
        throw new Exception('ID utilisateur invalide');
    }

    if ($other_user_id == $current_user_id) {
        throw new Exception('Vous ne pouvez pas créer une conversation avec vous-même');
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier que l'autre utilisateur existe
    $query = "SELECT id, pseudo FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();

    if (!$other_user) {
        throw new Exception('Utilisateur non trouvé');
    }

    // Vérifier s'il existe déjà une conversation entre ces deux utilisateurs
    $query = "
        SELECT c.id 
        FROM conversations c
        INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
        INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
        WHERE cp1.user_id = ? AND cp2.user_id = ?
        AND (
            SELECT COUNT(*) 
            FROM conversation_participants cp3 
            WHERE cp3.conversation_id = c.id
        ) = 2
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$current_user_id, $other_user_id]);
    $existing_conversation = $stmt->fetch();

    if ($existing_conversation) {
        // Conversation existante trouvée
        echo json_encode([
            'success' => true,
            'message' => 'Conversation existante trouvée',
            'conversation_id' => $existing_conversation['id'],
            'existing' => true
        ]);
        exit();
    }

    // Créer une nouvelle conversation
    $chat = new Chat();
    $conversation_name = null; // Laisser vide pour les conversations directes
    $participants = [$current_user_id, $other_user_id];

    $conversation_id = $chat->createConversation($conversation_name, $participants);

    if (!$conversation_id) {
        throw new Exception('Erreur lors de la création de la conversation');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Conversation créée avec succès',
        'conversation_id' => $conversation_id,
        'existing' => false
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
