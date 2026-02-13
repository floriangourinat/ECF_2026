<?php
/**
 * API Endpoint: Changement de mot de passe
 * POST /api/auth/change-password.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Méthode non autorisée"]);
    exit;
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

/**
 * Auth obligatoire (autorisé même si must_change_password = 1)
 */
$payload = require_auth([], true);
$userId = (int)$payload['user_id'];

$data = json_decode(file_get_contents("php://input"));

if (empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Mot de passe actuel et nouveau mot de passe requis."]);
    exit;
}

/**
 * Validation nouveau mot de passe
 */
$newPassword = (string)$data->new_password;

if (
    strlen($newPassword) < 8 ||
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/[0-9]/', $newPassword) ||
    !preg_match('/[^a-zA-Z0-9]/', $newPassword)
) {
    http_response_code(400);
    echo json_encode(["message" => "Le mot de passe ne respecte pas la politique de sécurité."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT id, password FROM users WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute([':id' => $userId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Utilisateur non trouvé."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify((string)$data->current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Mot de passe actuel incorrect."]);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmtUpdate = $db->prepare("UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id");
    $stmtUpdate->execute([
        ':password' => $hashedPassword,
        ':id' => $user['id']
    ]);

    http_response_code(200);
    echo json_encode(["message" => "Mot de passe modifié avec succès."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur."]);
}
?>
