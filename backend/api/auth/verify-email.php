<?php
/**
 * API Endpoint: Vérification email
 * GET /api/auth/verify-email.php?token=...
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Méthode non autorisée"]);
    exit;
}

include_once '../../config/database.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo json_encode(["message" => "Token invalide."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT id, email_verified FROM users WHERE email_verification_token = :token LIMIT 1");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Lien invalide ou expiré."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int)$user['email_verified'] === 1) {
        // Déjà vérifié -> OK (idempotent)
        http_response_code(200);
        echo json_encode(["message" => "Email déjà vérifié. Vous pouvez vous connecter."]);
        exit;
    }

    $upd = $db->prepare("
        UPDATE users
        SET email_verified = 1, email_verification_token = NULL, updated_at = NOW()
        WHERE id = :id
    ");
    $upd->bindParam(':id', $user['id'], PDO::PARAM_INT);
    $upd->execute();

    http_response_code(200);
    echo json_encode(["message" => "Email vérifié avec succès. Vous pouvez vous connecter."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur."]);
}
?>
