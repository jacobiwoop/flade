<?php
// api/push_subscription.php
require_once '../config/config.php';

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
    
    if (!$input || !isset($input['subscription'])) {
        throw new Exception('Données d\'abonnement manquantes');
    }
    
    $subscription = $input['subscription'];
    $user_id = $_SESSION['user_id'];
    
    // Valider les données d'abonnement
    if (!isset($subscription['endpoint']) || !isset($subscription['keys'])) {
        throw new Exception('Format d\'abonnement invalide');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Créer la table si elle n'existe pas
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh_key TEXT NOT NULL,
            auth_key TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_endpoint (user_id, endpoint(255))
        )
    ";
    $conn->exec($createTableQuery);
    
    // Insérer ou mettre à jour l'abonnement
    $query = "
        INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            p256dh_key = VALUES(p256dh_key),
            auth_key = VALUES(auth_key),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $user_id,
        $subscription['endpoint'],
        $subscription['keys']['p256dh'],
        $subscription['keys']['auth']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Abonnement push enregistré avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
