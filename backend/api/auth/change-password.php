<?php
/**
 * API Endpoint: Changement de mot de passe
 * backend/api/auth/change-password.php
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

// Récupérer le token depuis le header Authorization
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';

if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Token d'authentification requis."]);
    exit;
}

$token = $matches[1];
$decoded = json_decode(base64_decode($token), true);

if (!$decoded || !isset($decoded['id']) || !isset($decoded['exp'])) {
    http_response_code(401);
    echo json_encode(["message" => "Token invalide."]);
    exit;
}

// Vérifier expiration du token
if ($decoded['exp'] < time()) {
    http_response_code(401);
    echo json_encode(["message" => "Token expiré. Veuillez vous reconnecter."]);
    exit;
}

$user_id = $decoded['id'];

$data = json_decode(file_get_contents("php://input"));

if (empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Mot de passe actuel et nouveau mot de passe requis."]);
    exit;
}

// Validation nouveau mot de passe
$password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
if (!preg_match($password_regex, $data->new_password)) {
    http_response_code(400);
    echo json_encode([
        "message" => "Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial."
    ]);
    exit;
}

try {
    // Récupérer le mot de passe actuel
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Utilisateur non trouvé."]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier le mot de passe actuel
    if (!password_verify($data->current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Mot de passe actuel incorrect."]);
        exit;
    }

    // Mettre à jour le mot de passe
    $hashed = password_hash($data->new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $db->prepare("UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id");
    $update->bindParam(':password', $hashed);
    $update->bindParam(':id', $user_id);
    $update->execute();

    http_response_code(200);
    echo json_encode(["message" => "Mot de passe modifié avec succès."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}
?>