<?php
// backend/api/auth/login.php

// 1. HEADERS (Indispensable pour qu'Angular puisse discuter avec PHP)
header("Access-Control-Allow-Origin: *"); // Autorise tout le monde (pour le dev)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestion de la requête "preflight" (OPTIONS) envoyée par le navigateur avant le POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Connexion à la BDD
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 3. Récupération des données envoyées (JSON)
$data = json_decode(file_get_contents("php://input"));

// Vérification que les champs existent
if(!empty($data->email) && !empty($data->mot_de_passe)) {

    // 4. Préparation de la requête SQL
    // On cherche l'utilisateur par son email
    $query = "SELECT id, nom, prenom, mot_de_passe, role FROM utilisateurs WHERE email = :email LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    // 5. Analyse du résultat
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // On récupère le hash stocké en base
        $hash_bdd = $row['mot_de_passe'];
        $mot_de_passe_envoye = $data->mot_de_passe;

        // 6. Vérification du mot de passe (Hash vs Clair)
        if(password_verify($mot_de_passe_envoye, $hash_bdd)) {
            
            // --> SUCCÈS : Mot de passe correct
            http_response_code(200);
            echo json_encode([
                "message" => "Connexion réussie.",
                "user" => [
                    "id" => $row['id'],
                    "nom" => $row['nom'],
                    "prenom" => $row['prenom'],
                    "role" => $row['role']
                ]
            ]);

        } else {
            // --> ERREUR : Mauvais mot de passe
            http_response_code(401);
            echo json_encode(["message" => "Mot de passe incorrect."]);
        }
    } else {
        // --> ERREUR : Email inconnu
        http_response_code(401);
        echo json_encode(["message" => "Email incorrect ou compte inexistant."]);
    }
} else {
    // --> ERREUR : Champs vides
    http_response_code(400);
    echo json_encode(["message" => "Données incomplètes. Email et mot de passe requis."]);
}
?>