<?php

class Database
{
    // Configuration des paramètres de connexion à la base de données.
    // Dans l'environnement Docker, le host correspond au nom du service MySQL
    // défini dans docker-compose.yml, ici "db", et non "localhost".
    private string $host = "db";
    private string $db_name = "innovevents";
    private string $username = "root";
    private string $password = "root";

    public ?PDO $conn = null;

    /**
     * Établit et retourne une connexion PDO vers la base MySQL.
     *
     * L'encodage utf8mb4 est utilisé afin de gérer correctement l'ensemble
     * des caractères Unicode, y compris les accents, caractères spéciaux
     * et emojis éventuels.
     */
    public function getConnection(): ?PDO
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $this->conn->exec("SET NAMES utf8mb4");
        } catch (PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                "message" => "Erreur de connexion à la base de données."
            ]);
            exit();
        }

        return $this->conn;
    }
}