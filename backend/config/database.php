<?php
// backend/config/database.php

class Database {
    // Connexion à Docker (Port 3307)
    private $host = "127.0.0.1";
    private $port = "3307";
    private $db_name = "innovevents_db";
    
    // ON PASSE EN ROOT (Solution de force)
    private $username = "root";
    private $password = "root";
    
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";
            
            // Options importantes pour éviter les erreurs d'encodage ou de timeout
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>