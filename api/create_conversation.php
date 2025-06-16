<?php
// api/create_conversation.php
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
    $name = sanitize($_POST['name']);
    $participants_input = sanitize($_POST['participants']);
    
    if (empty($name) || empty($participants_input)) {
        throw new Exception('Tous les champs sont requis');
    }
    
    // Parser les participants (emails séparés par des virgules)
    $participant_emails = array_map('trim', explode(',', $participants_input));
    $participant_emails = array_filter($participant_emails); // Supprimer les éléments vides
    
    if (empty($participant_emails)) {
        throw new Exception('Au moins un participant est requis');
    }
    
    // Récupérer les IDs des utilisateurs à partir de leurs emails
    $user = new User();
    $participant_ids = [$_SESSION['user_id']]; // Inclure l'utilisateur actuel
    
    $database = new Database();
    $conn = $database->getConnection();
    
    foreach ($participant_emails as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide: $email");
        }
        
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            throw new Exception("Utilisateur non trouvé: $email");
        }
        
        if (!in_array($participant['id'], $participant_ids)) {
            $participant_ids[] = $participant['id'];
        }
    }
    
    // Créer la conversation
    $chat = new Chat();
    $conversation_id = $chat->createConversation($name, $participant_ids);
    
    if (!$conversation_id) {
        throw new Exception('Erreur lors de la création de la conversation');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Conversation créée avec succès',
        'conversation_id' => $conversation_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
