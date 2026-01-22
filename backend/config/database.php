<?php
class Database {
    // Configuration des paramètres de la base de données
    // IMPORTANT : Dans l'environnement Docker, le "host" correspond au nom du service MySQL 
    // défini dans le fichier docker-compose.yml (ici : "db"), et non "localhost".
    private $host = "db";
    private $db_name = "innovevents";
    private $username = "root";
    private $password = "root";
    public $conn;

    // Méthode pour établir et retourner la connexion à la base de données
    public function getConnection() {
        $this->conn = null;

        try {
            // Tentative de connexion via PDO avec les identifiants configurés
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            
            // Définition de l'encodage en UTF-8 pour gérer correctement les accents
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // En cas d'échec, affichage de l'erreur
            echo "Erreur de connexion : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>