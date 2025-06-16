<?php
// backend/auth/User.php
require_once dirname(__DIR__) . '/config/database.php';

class User
{
    private $conn;
    private $table = 'users';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($pseudo, $email, $password)
    {
        try {
            // Vérifier si l'utilisateur existe déjà
            $query = "SELECT id FROM " . $this->table . " WHERE pseudo = ? OR email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$pseudo, $email]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Ce pseudo ou cet email est déjà utilisé'];
            }

            // Hasher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur
            $query = "INSERT INTO " . $this->table . " (pseudo, email, password) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);

            if ($stmt->execute([$pseudo, $email, $hashed_password])) {
                return ['success' => true, 'message' => 'Inscription réussie'];
            }

            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public function login($login, $password)
    {
        try {
            // Chercher l'utilisateur par pseudo ou email
            $query = "SELECT id, pseudo, email, password FROM " . $this->table . " WHERE pseudo = ? OR email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$login, $login]);

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();

                if (password_verify($password, $user['password'])) {
                    // Mettre à jour le statut en ligne
                    $this->updateOnlineStatus($user['id'], true);

                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'pseudo' => $user['pseudo'],
                            'email' => $user['email']
                        ]
                    ];
                }
            }

            return ['success' => false, 'message' => 'Identifiants incorrects'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public function updateOnlineStatus($user_id, $is_online)
    {
        try {
            // Convertir le booléen en entier pour la base de données
            $online_status = $is_online ? 1 : 0;

            $query = "UPDATE " . $this->table . " SET is_online = ?, last_seen = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$online_status, $user_id]);
        } catch (Exception $e) {
            error_log("Erreur updateOnlineStatus: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id)
    {
        $query = "SELECT id, pseudo, email, is_online, last_seen,profile_photo, created_at FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateProfile($user_id, $pseudo, $current_password = '', $new_password = '')
    {
        try {
            // Vérifier si le pseudo est déjà utilisé par un autre utilisateur
            $query = "SELECT id FROM " . $this->table . " WHERE pseudo = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$pseudo, $user_id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Ce pseudo est déjà utilisé par un autre utilisateur'];
            }

            // Si un nouveau mot de passe est fourni, vérifier l'ancien
            if (!empty($new_password)) {
                $query = "SELECT password FROM " . $this->table . " WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if (!password_verify($current_password, $user['password'])) {
                    return ['success' => false, 'message' => 'Mot de passe actuel incorrect'];
                }

                // Mettre à jour avec le nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE " . $this->table . " SET pseudo = ?, password = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$pseudo, $hashed_password, $user_id]);
            } else {
                // Mettre à jour seulement le pseudo
                $query = "UPDATE " . $this->table . " SET pseudo = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$pseudo, $user_id]);
            }

            if ($result) {
                return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
            }

            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du profil'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
