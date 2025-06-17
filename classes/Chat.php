<?php
// classes/Chat.php
require_once dirname(__DIR__) . '/config/database.php';

class Chat
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Récupérer toutes les conversations d'un utilisateur avec les photos de profil
    public function getUserConversations($user_id)
    {
        $query = "
        SELECT 
            c.id,
            c.name,
            c.updated_at,
            COALESCE(last_msg.content, 'Aucun message') as last_message,
            COALESCE(last_msg.created_at, c.created_at) as last_message_time,
            COALESCE(sender.pseudo, 'Système') as last_sender,
            COALESCE(unread_count.count, 0) as unread_count,
            -- Récupérer le pseudo et la photo de l'autre utilisateur pour les conversations à 2 personnes
            CASE 
                WHEN participant_count.count = 2 THEN other_user.pseudo
                ELSE c.name
            END as display_name,
            CASE 
                WHEN participant_count.count = 2 THEN other_user.profile_photo
                ELSE NULL
            END as other_user_photo,
            participant_count.count as participant_count
        FROM conversations c
        INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
        LEFT JOIN (
            SELECT 
                m1.conversation_id,
                m1.content,
                m1.created_at,
                m1.user_id
            FROM messages m1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) as max_time
                FROM messages
                GROUP BY conversation_id
            ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_time
        ) last_msg ON c.id = last_msg.conversation_id
        LEFT JOIN users sender ON last_msg.user_id = sender.id
        LEFT JOIN (
            SELECT 
                m.conversation_id,
                COUNT(*) as count
            FROM messages m
            LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = ?
            WHERE mr.id IS NULL AND m.user_id != ?
            GROUP BY m.conversation_id
        ) unread_count ON c.id = unread_count.conversation_id
        LEFT JOIN (
            SELECT 
                conversation_id,
                COUNT(*) as count
            FROM conversation_participants
            GROUP BY conversation_id
        ) participant_count ON c.id = participant_count.conversation_id
        LEFT JOIN (
            SELECT 
                cp_other.conversation_id,
                u_other.pseudo,
                u_other.profile_photo
            FROM conversation_participants cp_other
            INNER JOIN users u_other ON cp_other.user_id = u_other.id
            WHERE cp_other.user_id != ?
        ) other_user ON c.id = other_user.conversation_id AND participant_count.count = 2
        WHERE cp.user_id = ?
        ORDER BY COALESCE(last_msg.created_at, c.created_at) DESC
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    }

    // Récupérer les informations d'une conversation avec les photos
    public function getConversationInfo($conversation_id)
    {
        $query = "
            SELECT 
                c.id,
                c.name,
                c.created_at,
                GROUP_CONCAT(u.pseudo SEPARATOR ', ') as participants,
                GROUP_CONCAT(u.profile_photo SEPARATOR ', ') as participant_photos
            FROM conversations c
            INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
            INNER JOIN users u ON cp.user_id = u.id
            WHERE c.id = ?
            GROUP BY c.id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$conversation_id]);
        return $stmt->fetch();
    }

    // Récupérer les informations de l'autre utilisateur dans une conversation directe
    public function getOtherUserInConversation($conversation_id, $current_user_id)
    {
        $query = "
            SELECT u.id, u.pseudo, u.profile_photo, u.is_online, u.last_seen
            FROM users u
            INNER JOIN conversation_participants cp ON u.id = cp.user_id
            WHERE cp.conversation_id = ? AND u.id != ?
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$conversation_id, $current_user_id]);
        return $stmt->fetch();
    }

    // Récupérer les messages d'une conversation
    public function getConversationMessages($conversation_id, $user_id, $limit = 50)
    {
        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$this->isUserInConversation($user_id, $conversation_id)) {
            return false;
        }

        // Récupérer les derniers messages (ORDER BY DESC) puis les inverser
        $query = "
    SELECT 
        m.id,
        m.content,
        m.created_at,
        m.reply_to_message_id,
        m.image_path,
        m.voice_path,
        m.is_deleted,
        u.pseudo as sender_pseudo,
        u.profile_photo as sender_photo,
        m.user_id as sender_id,
        -- Informations du message de réponse
        reply_msg.content as reply_content,
        reply_msg.user_id as reply_user_id,
        reply_user.pseudo as reply_user_pseudo,
        reply_msg.image_path as reply_image_path,
        reply_msg.voice_path as reply_voice_path,
        -- Informations de lecture
        (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) as read_count,
        (SELECT mr.read_at FROM message_reads mr WHERE mr.message_id = m.id AND mr.user_id = ?) as user_read_at
    FROM messages m
    INNER JOIN users u ON m.user_id = u.id
    LEFT JOIN messages reply_msg ON m.reply_to_message_id = reply_msg.id AND reply_msg.is_deleted = FALSE
    LEFT JOIN users reply_user ON reply_msg.user_id = reply_user.id
    WHERE m.conversation_id = ? AND m.is_deleted = FALSE
    ORDER BY m.created_at DESC
    LIMIT ?
";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $conversation_id, $limit]);
        $messages = $stmt->fetchAll();
        
        // Inverser l'ordre pour afficher du plus ancien au plus récent
        return array_reverse($messages);
    }

    // Envoyer un message
    public function sendMessage($conversation_id, $user_id, $content, $reply_to_message_id = null, $image_path = null, $voice_path = null)
    {
        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$this->isUserInConversation($user_id, $conversation_id)) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            // Insérer le message avec le support vocal
            $query = "INSERT INTO messages (conversation_id, user_id, content, reply_to_message_id, image_path, voice_path) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$conversation_id, $user_id, $content, $reply_to_message_id, $image_path, $voice_path]);
            
            if (!$result) {
                throw new Exception('Erreur lors de l\'insertion du message');
            }
            
            $message_id = $this->conn->lastInsertId();

            // Mettre à jour la date de la conversation
            $query = "UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$conversation_id]);

            // Marquer le message comme lu par l'expéditeur
            $this->markMessageAsRead($message_id, $user_id);

            $this->conn->commit();

            // Récupérer le message complet pour le retourner
            $query = "
        SELECT 
            m.id,
            m.content,
            m.created_at,
            m.reply_to_message_id,
            m.image_path,
            m.voice_path,
            m.is_deleted,
            u.pseudo as sender_pseudo,
            u.profile_photo as sender_photo,
            m.user_id as sender_id,
            reply_msg.content as reply_content,
            reply_msg.user_id as reply_user_id,
            reply_user.pseudo as reply_user_pseudo,
            reply_msg.image_path as reply_image_path,
            reply_msg.voice_path as reply_voice_path
        FROM messages m
        INNER JOIN users u ON m.user_id = u.id
        LEFT JOIN messages reply_msg ON m.reply_to_message_id = reply_msg.id AND reply_msg.is_deleted = FALSE
        LEFT JOIN users reply_user ON reply_msg.user_id = reply_user.id
        WHERE m.id = ?
    ";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$message_id]);

            $message = $stmt->fetch();
            
            if (!$message) {
                throw new Exception('Message non trouvé après insertion');
            }

            return $message;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Erreur sendMessage: ' . $e->getMessage());
            return false;
        }
    }

    // Marquer un message comme lu
    public function markMessageAsRead($message_id, $user_id)
    {
        $query = "INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$message_id, $user_id]);
    }

    // Marquer tous les messages d'une conversation comme lus
    public function markConversationAsRead($conversation_id, $user_id)
    {
        $query = "
            INSERT IGNORE INTO message_reads (message_id, user_id)
            SELECT m.id, ? 
            FROM messages m 
            WHERE m.conversation_id = ? AND m.is_deleted = FALSE
        ";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $conversation_id]);
    }

    // Vérifier si un utilisateur fait partie d'une conversation
    public function isUserInConversation($user_id, $conversation_id)
    {
        $query = "SELECT id FROM conversation_participants WHERE user_id = ? AND conversation_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $conversation_id]);
        return $stmt->rowCount() > 0;
    }

    // Créer une nouvelle conversation
    public function createConversation($name, $participants)
    {
        try {
            $this->conn->beginTransaction();

            // Créer la conversation
            $query = "INSERT INTO conversations (name) VALUES (?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$name]);
            $conversation_id = $this->conn->lastInsertId();

            // Ajouter les participants
            $query = "INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);

            foreach ($participants as $user_id) {
                $stmt->execute([$conversation_id, $user_id]);
            }

            $this->conn->commit();
            return $conversation_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Récupérer les nouveaux messages depuis un ID donné
    public function getNewMessages($conversation_id, $last_message_id = 0, $limit = 15)
    {
        $query = "
    SELECT 
        m.id,
        m.content,
        m.created_at,
        m.reply_to_message_id,
        m.image_path,
        m.voice_path,
        m.is_deleted,
        u.pseudo as sender_pseudo,
        u.profile_photo as sender_photo,
        m.user_id as sender_id,
        reply_msg.content as reply_content,
        reply_msg.user_id as reply_user_id,
        reply_user.pseudo as reply_user_pseudo,
        reply_msg.image_path as reply_image_path,
        reply_msg.voice_path as reply_voice_path
    FROM messages m
    INNER JOIN users u ON m.user_id = u.id
    LEFT JOIN messages reply_msg ON m.reply_to_message_id = reply_msg.id AND reply_msg.is_deleted = FALSE
    LEFT JOIN users reply_user ON reply_msg.user_id = reply_user.id
    WHERE m.conversation_id = ? AND m.id > ? AND m.is_deleted = FALSE
    ORDER BY m.created_at ASC
    LIMIT ?
";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$conversation_id, $last_message_id, $limit]);
        return $stmt->fetchAll();
    }

    // Récupérer les messages plus anciens pour la pagination
    public function getOlderMessages($conversation_id, $user_id, $oldest_message_id, $limit = 15)
    {
        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$this->isUserInConversation($user_id, $conversation_id)) {
            return false;
        }

        $query = "
    SELECT 
        m.id,
        m.content,
        m.created_at,
        m.reply_to_message_id,
        m.image_path,
        m.voice_path,
        m.is_deleted,
        u.pseudo as sender_pseudo,
        u.profile_photo as sender_photo,
        m.user_id as sender_id,
        -- Informations du message de réponse
        reply_msg.content as reply_content,
        reply_msg.user_id as reply_user_id,
        reply_user.pseudo as reply_user_pseudo,
        reply_msg.image_path as reply_image_path,
        reply_msg.voice_path as reply_voice_path,
        -- Informations de lecture
        (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) as read_count,
        (SELECT mr.read_at FROM message_reads mr WHERE mr.message_id = m.id AND mr.user_id = ?) as user_read_at
    FROM messages m
    INNER JOIN users u ON m.user_id = u.id
    LEFT JOIN messages reply_msg ON m.reply_to_message_id = reply_msg.id AND reply_msg.is_deleted = FALSE
    LEFT JOIN users reply_user ON reply_msg.user_id = reply_user.id
    WHERE m.conversation_id = ? AND m.id < ? AND m.is_deleted = FALSE
    ORDER BY m.created_at DESC
    LIMIT ?
";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $conversation_id, $oldest_message_id, $limit]);
        return $stmt->fetchAll();
    }

    // Ajouter une méthode pour obtenir les utilisateurs qui ont lu un message
    public function getMessageReaders($message_id)
    {
        $query = "
        SELECT 
            u.id,
            u.pseudo,
            u.profile_photo,
            mr.read_at
        FROM message_reads mr
        INNER JOIN users u ON mr.user_id = u.id
        WHERE mr.message_id = ?
        ORDER BY mr.read_at ASC
    ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$message_id]);
        return $stmt->fetchAll();
    }
}
