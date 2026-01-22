<?php
// backend/config/database.php

class Database {
    // Configuration de l'accès à la base de données
    // Note : On pointe sur 127.0.0.1 pour cibler le conteneur Docker depuis Windows
    private $host = "127.0.0.1";
    private $port = "3307"; // Port défini dans le docker-compose.yml
    private $db_name = "innovevents_db";
    
    // Identifiants de connexion
    // Utilisation du compte 'root' pour l'environnement de développement local
    private $username = "root";
    private $password = "root";
    
    public $conn;

    // Méthode pour récupérer l'instance de connexion PDO
    public function getConnection() {
        $this->conn = null;

        try {
            // Création de la chaîne de connexion (DSN) avec encodage UTF-8
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";
            
            // Configuration des options PDO pour une meilleure gestion des erreurs
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active les exceptions pour les erreurs SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Récupère les données en tableau associatif
                PDO::ATTR_EMULATE_PREPARES => false, // Utilise les vraies requêtes préparées
            ];

            // Initialisation de la connexion
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Erreur critique de connexion BDD : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>