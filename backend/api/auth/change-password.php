<?php
/**
 * API Endpoint: Changement de mot de passe
 * POST /api/auth/change-password.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Méthode non autorisée"]);
    exit;
}

include_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

// Validation
if (empty($data->user_id) || empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Données incomplètes. ID utilisateur, mot de passe actuel et nouveau mot de passe requis."]);
    exit;
}

// Validation nouveau mot de passe (min 8 caractères, 1 majuscule, 1 chiffre)
if (strlen($data->new_password) < 8) {
    http_response_code(400);
    echo json_encode(["message" => "Le nouveau mot de passe doit contenir au moins 8 caractères."]);
    exit;
}

if (!preg_match('/[A-Z]/', $data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Le nouveau mot de passe doit contenir au moins une majuscule."]);
    exit;
}

if (!preg_match('/[0-9]/', $data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Le nouveau mot de passe doit contenir au moins un chiffre."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer l'utilisateur
    $stmt = $db->prepare("SELECT id, password FROM users WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute([':id' => (int)$data->user_id]);

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
    $hashedPassword = password_hash($data->new_password, PASSWORD_BCRYPT);
    $stmtUpdate = $db->prepare("UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id");
    $stmtUpdate->execute([
        ':password' => $hashedPassword,
        ':id' => $user['id']
    ]);

    http_response_code(200);
    echo json_encode(["message" => "Mot de passe modifié avec succès."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}
?>
