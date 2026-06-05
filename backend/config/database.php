<?php

class Database
{
    // Configuration des paramètres de la base de données.
    // Dans l'environnement Docker, le host correspond au nom du service MySQL
    // défini dans docker-compose.yml, ici "db", et non "localhost".
    private $host = "db";
    private $db_name = "innovevents";
    private $username = "root";
    private $password = "root";
    public $conn;

    // Établit et retourne une connexion PDO réutilisable dans les endpoints de l'API.
    public function getConnection()
    {
        $this->conn = null;

        try {
            // Connexion PDO à MySQL avec encodage utf8mb4 pour gérer correctement l'Unicode.
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );

            // Configuration de PDO pour remonter les erreurs SQL sous forme d'exceptions.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Encodage explicite des échanges avec MySQL.
            $this->conn->exec("SET NAMES utf8mb4");
        } catch (PDOException $exception) {
            // En cas d'échec, affichage de l'erreur de connexion.
            echo "Erreur de connexion : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>