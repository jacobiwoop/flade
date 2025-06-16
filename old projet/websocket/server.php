<?php
// websocket/server.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Chat.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    protected $users;
    protected $chat;
    protected $pdo;
    protected $lastPingTime;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->initializeDatabase();
        $this->lastPingTime = time();
        echo "Serveur WebSocket démarré\n";

        // Timer pour maintenir la connexion MySQL active
        $this->setupMySQLKeepAlive();
    }

    private function initializeDatabase()
    {
        try {
            $DB_HOST = 'localhost'; // Remplacez par votre hôte de base de données
            $DB_NAME = 'chat_app'; // Remplacez par le nom de votre base de données 
            $DB_USER = 'root'; // Remplacez par votre utilisateur de base de données
            $DB_PASS = ''; // Remplacez par votre mot de passe de base de données
            // Créer une nouvelle instance de connexion PDO avec des options spécifiques
            $dsn = "mysql:host=" . $DB_HOST .    ";dbname=" . $DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                //  PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
                // Augmenter le timeout de connexion
                PDO::ATTR_TIMEOUT => 60,
                // Activer la reconnexion automatique
                PDO::MYSQL_ATTR_COMPRESS => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];

            $this->pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
            $this->chat = new Chat();

            echo "Connexion à la base de données établie\n";
        } catch (PDOException $e) {
            echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function setupMySQLKeepAlive()
    {
        // Créer un timer pour maintenir la connexion MySQL active
        $loop = \React\EventLoop\Factory::create();
        $loop->addPeriodicTimer(300, function () { // Toutes les 5 minutes
            $this->pingDatabase();
        });
    }

    private function pingDatabase()
    {
        try {
            // Ping simple pour maintenir la connexion active
            $stmt = $this->pdo->query("SELECT 1");
            $stmt->fetch();
            echo "Ping base de données réussi\n";
        } catch (PDOException $e) {
            echo "Erreur ping base de données: " . $e->getMessage() . "\n";
            echo "Tentative de reconnexion...\n";
            $this->initializeDatabase();
        }
    }

    private function ensureDatabaseConnection()
    {
        try {
            // Vérifier si la connexion est encore active
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            echo "Connexion base de données perdue, reconnexion...\n";
            $this->initializeDatabase();
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Nouvelle connexion: {$conn->resourceId}\n";

        // Envoyer un message de bienvenue pour confirmer la connexion
        $conn->send(json_encode([
            'type' => 'connection_established',
            'message' => 'Connexion WebSocket établie'
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);

            if (!$data) {
                throw new Exception('Message JSON invalide');
            }

            echo "Message reçu: " . json_encode($data) . "\n";

            // S'assurer que la base de données est connectée
            $this->ensureDatabaseConnection();

            switch ($data['type']) {
                case 'auth':
                    $this->handleAuth($from, $data);
                    break;

                case 'message':
                    $this->handleMessage($from, $data);
                    break;

                case 'delete_message':
                    $this->handleDeleteMessage($from, $data);
                    break;

                case 'join_conversation':
                    $this->handleJoinConversation($from, $data);
                    break;

                case 'typing':
                    $this->handleTyping($from, $data);
                    break;

                case 'call_invite':
                    $this->handleCallInvite($from, $data);
                    break;

                case 'call_accept':
                    $this->handleCallAccept($from, $data);
                    break;

                case 'call_reject':
                    $this->handleCallReject($from, $data);
                    break;

                case 'call_end':
                    $this->handleCallEnd($from, $data);
                    break;

                case 'ping':
                    // Répondre au ping du client
                    $from->send(json_encode(['type' => 'pong']));
                    break;

                default:
                    throw new Exception('Type de message non reconnu: ' . $data['type']);
            }
        } catch (Exception $e) {
            echo "Erreur lors du traitement du message: " . $e->getMessage() . "\n";
            $this->sendError($from, $e->getMessage());
        }
    }

    private function handleAuth($conn, $data)
    {
        if (!isset($data['user_id']) || !isset($data['conversation_id'])) {
            throw new Exception('Données d\'authentification manquantes');
        }

        $user_id = intval($data['user_id']);
        $conversation_id = intval($data['conversation_id']);

        echo "Tentative d'authentification: utilisateur {$user_id}, conversation {$conversation_id}\n";

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$this->chat->isUserInConversation($user_id, $conversation_id)) {
            throw new Exception('Accès non autorisé à cette conversation');
        }

        // Enregistrer l'utilisateur
        $this->users[$conn->resourceId] = [
            'user_id' => $user_id,
            'conversation_id' => $conversation_id,
            'connection' => $conn,
            'authenticated_at' => time()
        ];

        // Marquer la conversation comme lue
        try {
            $this->chat->markConversationAsRead($conversation_id, $user_id);
        } catch (Exception $e) {
            echo "Erreur lors du marquage comme lu: " . $e->getMessage() . "\n";
        }

        $this->sendSuccess($conn, 'Authentification réussie');
        echo "Utilisateur {$user_id} authentifié pour la conversation {$conversation_id}\n";

        // Notifier les autres utilisateurs
        $this->broadcastToConversation($conversation_id, [
            'type' => 'user_joined',
            'user_id' => $user_id
        ], $conn);
    }

    private function handleMessage($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $content = isset($data['content']) ? trim($data['content']) : '';
        $reply_to_message_id = isset($data['reply_to_message_id']) ? intval($data['reply_to_message_id']) : null;
        $image_path = isset($data['image_path']) ? $data['image_path'] : null;
        $voice_path = isset($data['voice_path']) ? $data['voice_path'] : null;

        // Vérifier qu'il y a au moins du contenu, une image ou un message vocal
        if (empty($content) && empty($image_path) && empty($voice_path)) {
            throw new Exception('Message vide');
        }

        if (!empty($content) && strlen($content) > 1000) {
            throw new Exception('Message trop long');
        }

        echo "Envoi message utilisateur {$user['user_id']}: " . substr($content ?: ($voice_path ? '[Message vocal]' : '[Image]'), 0, 50) . "...\n";

        // Sauvegarder le message en base
        try {
            $message = $this->chat->sendMessage(
                $user['conversation_id'],
                $user['user_id'],
                $content,
                $reply_to_message_id,
                $image_path,
                $voice_path
            );

            if (!$message) {
                throw new Exception('Erreur lors de l\'envoi du message');
            }

            echo "Message sauvegardé avec ID: " . $message['id'] . "\n";
            if ($voice_path) {
                echo "Chemin vocal: " . $voice_path . "\n";
            }

            // Diffuser le message à tous les participants de la conversation
            $this->broadcastToConversation($user['conversation_id'], [
                'type' => 'new_message',
                'message' => $message
            ]);

            echo "Message diffusé avec succès\n";
        } catch (Exception $e) {
            echo "Erreur envoi message: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function handleDeleteMessage($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $message_id = isset($data['message_id']) ? intval($data['message_id']) : 0;

        if ($message_id <= 0) {
            throw new Exception('ID de message invalide');
        }

        // Vérifier que le message appartient à l'utilisateur
        $query = "SELECT user_id, image_path, voice_path FROM messages WHERE id = ? AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();

        if (!$message) {
            throw new Exception('Message non trouvé');
        }

        if ($message['user_id'] != $user['user_id']) {
            throw new Exception('Vous ne pouvez supprimer que vos propres messages');
        }

        // Supprimer le message (soft delete)
        $query = "UPDATE messages SET is_deleted = TRUE, deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$message_id]);

        // Supprimer les fichiers associés si ils existent
        if ($message['image_path'] && file_exists('../' . $message['image_path'])) {
            unlink('../' . $message['image_path']);
        }
        if ($message['voice_path'] && file_exists('../' . $message['voice_path'])) {
            unlink('../' . $message['voice_path']);
        }

        // Diffuser la suppression
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'message_deleted',
            'message_id' => $message_id
        ]);

        echo "Message {$message_id} supprimé par utilisateur {$user['user_id']}\n";
    }

    private function handleJoinConversation($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];

        // Notifier les autres utilisateurs qu'un utilisateur a rejoint
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'user_joined',
            'user_id' => $user['user_id']
        ], $conn);
    }

    private function handleTyping($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $is_typing = isset($data['is_typing']) ? (bool)$data['is_typing'] : false;

        // Diffuser l'indicateur de frappe aux autres participants
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'typing',
            'user_id' => $user['user_id'],
            'is_typing' => $is_typing
        ], $conn);
    }

    private function handleCallInvite($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $call_type = isset($data['call_type']) ? $data['call_type'] : 'voice';

        echo "Invitation d'appel {$call_type} de l'utilisateur {$user['user_id']}\n";

        // Diffuser l'invitation d'appel aux autres participants
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'call_invite',
            'caller_id' => $user['user_id'],
            'call_type' => $call_type,
            'conversation_id' => $user['conversation_id']
        ], $conn);
    }

    private function handleCallAccept($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $caller_id = isset($data['caller_id']) ? intval($data['caller_id']) : 0;

        echo "Appel accepté par l'utilisateur {$user['user_id']}\n";

        // Notifier l'appelant que l'appel a été accepté
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'call_accepted',
            'accepter_id' => $user['user_id'],
            'caller_id' => $caller_id
        ]);
    }

    private function handleCallReject($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];
        $caller_id = isset($data['caller_id']) ? intval($data['caller_id']) : 0;

        echo "Appel rejeté par l'utilisateur {$user['user_id']}\n";

        // Notifier l'appelant que l'appel a été rejeté
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'call_rejected',
            'rejecter_id' => $user['user_id'],
            'caller_id' => $caller_id
        ]);
    }

    private function handleCallEnd($conn, $data)
    {
        if (!isset($this->users[$conn->resourceId])) {
            throw new Exception('Utilisateur non authentifié');
        }

        $user = $this->users[$conn->resourceId];

        echo "Appel terminé par l'utilisateur {$user['user_id']}\n";

        // Notifier tous les participants que l'appel est terminé
        $this->broadcastToConversation($user['conversation_id'], [
            'type' => 'call_ended',
            'user_id' => $user['user_id']
        ]);
    }

    private function broadcastToConversation($conversation_id, $data, $exclude = null)
    {
        $message = json_encode($data);
        $sent_count = 0;

        foreach ($this->users as $resourceId => $user) {
            if ($user['conversation_id'] == $conversation_id && $user['connection'] !== $exclude) {
                try {
                    $user['connection']->send($message);
                    $sent_count++;
                } catch (Exception $e) {
                    echo "Erreur envoi à l'utilisateur {$user['user_id']}: " . $e->getMessage() . "\n";
                    // Nettoyer la connexion fermée
                    unset($this->users[$resourceId]);
                }
            }
        }

        echo "Message diffusé à {$sent_count} utilisateurs dans la conversation {$conversation_id}\n";
    }

    private function sendSuccess($conn, $message)
    {
        $conn->send(json_encode([
            'type' => 'success',
            'message' => $message
        ]));
    }

    private function sendError($conn, $message)
    {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message
        ]));
    }

    public function onClose(ConnectionInterface $conn)
    {
        if (isset($this->users[$conn->resourceId])) {
            $user = $this->users[$conn->resourceId];

            // Notifier les autres utilisateurs qu'un utilisateur s'est déconnecté
            $this->broadcastToConversation($user['conversation_id'], [
                'type' => 'user_left',
                'user_id' => $user['user_id']
            ], $conn);

            unset($this->users[$conn->resourceId]);
            echo "Utilisateur {$user['user_id']} déconnecté\n";
        }

        $this->clients->detach($conn);
        echo "Connexion fermée: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erreur sur la connexion {$conn->resourceId}: {$e->getMessage()}\n";

        // Nettoyer l'utilisateur s'il existe
        if (isset($this->users[$conn->resourceId])) {
            unset($this->users[$conn->resourceId]);
        }

        $conn->close();
    }
}

// Configuration du serveur avec gestion d'erreurs
try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new ChatServer()
            )
        ),
        8080
    );

    echo "Serveur WebSocket en écoute sur le port 8080\n";
    echo "Appuyez sur Ctrl+C pour arrêter le serveur\n";

    $server->run();
} catch (Exception $e) {
    echo "Erreur lors du démarrage du serveur: " . $e->getMessage() . "\n";
    exit(1);
}
