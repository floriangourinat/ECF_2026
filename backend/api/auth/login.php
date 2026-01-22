<?php
/**
 * API Endpoint: Connexion utilisateur (Login)
 * Méthode: POST
 * URL: /api/auth/login.php
 */

// 1. Gestion des CORS (Indispensable pour Angular)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si c'est une requête de vérification (OPTIONS), on arrête là (Angular le fait souvent)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Connexion à la base de données
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 3. Récupération des données envoyées par Angular
$data = json_decode(file_get_contents("php://input"));

// Vérification que l'email et le mot de passe sont présents
if (!empty($data->email) && !empty($data->password)) {

    // On cherche l'utilisateur par son email
    $query = "SELECT id, nom, prenom, email, password, role FROM users WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    
    // Nettoyage et binding
    $email = htmlspecialchars(strip_tags($data->email));
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérification du mot de passe haché
        // Note: Le mot de passe "123456" inséré via SQL correspond au hash que je vous ai donné
        if (password_verify($data->password, $row['password'])) {
            
            // Tout est bon ! On crée le Token (en vrai projet, on utiliserait JWT)
            // Ici on renvoie un token simple pour l'exercice
            $token_payload = array(
                "id" => $row['id'],
                "email" => $row['email'],
                "role" => $row['role']
            );

            http_response_code(200);
            echo json_encode(array(
                "message" => "Connexion réussie.",
                "token" => base64_encode(json_encode($token_payload)), // Simulation de token
                "user" => array(
                    "id" => $row['id'],
                    "nom" => $row['nom'],
                    "prenom" => $row['prenom'],
                    "role" => $row['role']
                )
            ));
        } else {
            // Mauvais mot de passe
            http_response_code(401);
            echo json_encode(array("message" => "Mot de passe incorrect."));
        }
    } else {
        // Email introuvable
        http_response_code(401);
        echo json_encode(array("message" => "Cet email n'existe pas."));
    }
} else {
    // Données incomplètes
    http_response_code(400);
    echo json_encode(array("message" => "Données incomplètes. Email et mot de passe requis."));
}
?>