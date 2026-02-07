<?php
/**
 * API Endpoint: Inscription utilisateur
 * backend/api/auth/register.php
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

// Validation champs requis
$required = ['email', 'password', 'last_name', 'first_name', 'username'];
foreach ($required as $field) {
    if (empty($data->$field)) {
        http_response_code(400);
        echo json_encode(["message" => "Le champ '$field' est requis."]);
        exit;
    }
}

// Validation email
$email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

// Validation mot de passe : 8 car, 1 maj, 1 min, 1 chiffre, 1 spécial
$password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
if (!preg_match($password_regex, $data->password)) {
    http_response_code(400);
    echo json_encode([
        "message" => "Mot de passe : 8 caractères min, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial (@\$!%*?&)."
    ]);
    exit;
}

$last_name = htmlspecialchars(strip_tags(trim($data->last_name)));
$first_name = htmlspecialchars(strip_tags(trim($data->first_name)));
$username = htmlspecialchars(strip_tags(trim($data->username)));

try {
    // Vérif email unique
    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Cet email est déjà utilisé."]);
        exit;
    }

    // Vérif username unique
    $check = $db->prepare("SELECT id FROM users WHERE username = :username");
    $check->bindParam(':username', $username);
    $check->execute();
    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Ce nom d'utilisateur est déjà pris."]);
        exit;
    }

    // Token de vérification email
    $verification_token = bin2hex(random_bytes(32));
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]);

    $query = "INSERT INTO users (last_name, first_name, username, email, password, role, is_active, email_verified, email_verification_token) 
              VALUES (:last_name, :first_name, :username, :email, :password, 'client', 1, 0, :token)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':token', $verification_token);
    
    if ($stmt->execute()) {
        // TODO: Envoyer email de confirmation avec PHPMailer
        
        http_response_code(201);
        echo json_encode([
            "message" => "Compte créé avec succès ! Un email de confirmation vous a été envoyé.",
            "user_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Erreur lors de la création du compte."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}
?>