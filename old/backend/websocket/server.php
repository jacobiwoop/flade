<?php
// backend/websocket/server.php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../classes/Chat.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        echo "Serveur WebSocket Floade Chat démarré\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                return;
            }

            switch ($data['type']) {
                case 'auth':
                    $this->handleAuth($from, $data);
                    break;
                case 'message':
                    $this->handleMessage($from, $data);
                    break;
                case 'typing':
                    $this->handleTyping($from, $data);
                    break;
            }
        } catch (Exception $e) {
            echo "Erreur lors du traitement du message: " . $e->getMessage() . "\n";
        }
    }

    private function handleAuth($conn, $data) {
        if (isset($data['user_id'])) {
            $this->userConnections[$data['user_id']] = $conn;
            $conn->user_id = $data['user_id'];
            echo "Utilisateur {$data['user_id']} authentifié\n";
        }
    }

    private function handleMessage($from, $data) {
        if (!isset($from->user_id) || !isset($data['conversation_id'])) {
            return;
        }

        // Récupérer les participants de la conversation
        $chat = new Chat();
        $participants = $this->getConversationParticipants($data['conversation_id']);

        // Envoyer le message à tous les participants connectés
        foreach ($participants as $participant_id) {
            if (isset($this->userConnections[$participant_id]) && $participant_id != $from->user_id) {
                $this->userConnections[$participant_id]->send(json_encode([
                    'type' => 'new_message',
                    'message' => $data['message'],
                    'conversation_id' => $data['conversation_id']
                ]));
            }
        }
    }

    private function handleTyping($from, $data) {
        if (!isset($from->user_id) || !isset($data['conversation_id'])) {
            return;
        }

        $participants = $this->getConversationParticipants($data['conversation_id']);

        foreach ($participants as $participant_id) {
            if (isset($this->userConnections[$participant_id]) && $participant_id != $from->user_id) {
                $this->userConnections[$participant_id]->send(json_encode([
                    'type' => 'typing',
                    'user_id' => $from->user_id,
                    'conversation_id' => $data['conversation_id'],
                    'is_typing' => $data['is_typing']
                ]));
            }
        }
    }

    private function getConversationParticipants($conversation_id) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT user_id FROM conversation_participants WHERE conversation_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$conversation_id]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            echo "Erreur lors de la récupération des participants: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($conn->user_id)) {
            unset($this->userConnections[$conn->user_id]);
            echo "Utilisateur {$conn->user_id} déconnecté\n";
        }
        
        echo "Connexion fermée: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erreur: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Démarrer le serveur
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "Serveur WebSocket en écoute sur le port 8080\n";
$server->run();
