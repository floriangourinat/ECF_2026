<?php
// backend/config/database_mongo.php

// On charge les librairies installées via Composer (indispensable pour MongoDB)
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

class DatabaseMongo {
    // Dans Docker, le nom du service est "mongo" (voir docker-compose.yml)
    private $host = "mongo"; 
    private $port = "27017";
    private $db_name = "innovevents_nosql"; // Une base séparée pour le NoSQL
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Construction de l'URL de connexion (ConnectionString)
            // Format: mongodb://user:pass@host:port
            $uri = "mongodb://{$this->username}:{$this->password}@{$this->host}:{$this->port}";
            
            // Création du client MongoDB
            $client = new Client($uri);
            
            // On sélectionne la base de données
            $this->conn = $client->selectDatabase($this->db_name);
            
        } catch(Exception $exception) {
            echo "Erreur de connexion MongoDB : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>