<?php
require_once '../config/config.php';
require_once '../classes/Chat.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $chat = new Chat();
    $conversations = $chat->getUserConversations($_SESSION['user_id']);

    // Fonction pour obtenir l'URL de la photo de profil
    function getProfilePhotoUrl($photo)
    {
        if ($photo && file_exists('../uploads/profiles/' . $photo)) {
            return 'uploads/profiles/' . $photo;
        }
        return null;
    }

    // Formater les données pour le frontend
    $formattedConversations = array_map(function ($conv) {
        return [
            'id' => intval($conv['id']),
            'display_name' => $conv['display_name'] ?: ($conv['name'] ?: 'Conversation'),
            'last_message' => $conv['last_message'] ?: 'Aucun message',
            'last_message_time' => $conv['last_message_time'],
            'unread_count' => intval($conv['unread_count']),
            'other_user_photo' => getProfilePhotoUrl($conv['other_user_photo']),
            'participant_count' => intval($conv['participant_count'] ?? 2)
        ];
    }, $conversations);

    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations,
        'count' => count($formattedConversations)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des conversations: ' . $e->getMessage()
    ]);
}
