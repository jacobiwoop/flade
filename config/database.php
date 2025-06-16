<?php
// config/database.php
class Database
{
    private $host = 'mnz.domcloud.co';
    private $db_name = 'etransfer_coach_miw_db';
    private $username = 'etransfer-coach-miw';
    private $password = 'jKqvTl6G5J6W9j(-3)';
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            echo "Erreur de connexion: " . $e->getMessage();
        }

        return $this->conn;
    }
}
