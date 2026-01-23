<?php
/**
 * API Endpoint: Connexion utilisateur
 * backend/api/auth/login.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Validation
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Données incomplètes. Email et mot de passe requis."]);
    exit;
}

$email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

try {
    $query = "SELECT id, last_name, first_name, username, email, password, role, 
                     is_active, must_change_password 
              FROM users WHERE email = :email LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Identifiants incorrects."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Compte suspendu ?
    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(["message" => "Compte suspendu. Contactez l'administrateur."]);
        exit;
    }

    // Vérification mot de passe
    if (!password_verify($data->password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Identifiants incorrects."]);
        exit;
    }

    // Création token
    $token_payload = [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role'],
        "iat" => time(),
        "exp" => time() + 86400
    ];

    http_response_code(200);
    echo json_encode([
        "message" => "Connexion réussie.",
        "token" => base64_encode(json_encode($token_payload)),
        "user" => [
            "id" => $user['id'],
            "last_name" => $user['last_name'],
            "first_name" => $user['first_name'],
            "username" => $user['username'],
            "email" => $user['email'],
            "role" => $user['role'],
            "must_change_password" => (bool) $user['must_change_password']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}
?>