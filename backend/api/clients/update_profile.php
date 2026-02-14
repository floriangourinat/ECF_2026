<?php
/**
 * API: Modifier profil client
 * PUT /api/clients/update_profile.php
 * Body JSON: { user_id, first_name, last_name, email, company_name, phone, address }
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['admin', 'client']);
$authUserId = (int)$payload['user_id'];
$role = $payload['role'] ?? '';

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

$targetUserId = (int)$data['user_id'];
if ($role === 'client' && $targetUserId !== $authUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare('SELECT id FROM clients WHERE user_id = :user_id LIMIT 1');
    $stmtClient->execute([':user_id' => $targetUserId]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtUser = $db->prepare(
        'UPDATE users
         SET first_name = :first_name, last_name = :last_name, email = :email
         WHERE id = :user_id'
    );
    $stmtUser->execute([
        ':first_name' => htmlspecialchars(strip_tags(trim((string)($data['first_name'] ?? '')))),
        ':last_name' => htmlspecialchars(strip_tags(trim((string)($data['last_name'] ?? '')))),
        ':email' => filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL),
        ':user_id' => $targetUserId
    ]);

    $stmtUpdateClient = $db->prepare(
        'UPDATE clients
         SET company_name = :company_name, phone = :phone, address = :address
         WHERE user_id = :user_id'
    );
    $stmtUpdateClient->execute([
        ':company_name' => !empty($data['company_name']) ? htmlspecialchars(strip_tags(trim((string)$data['company_name']))) : null,
        ':phone' => !empty($data['phone']) ? htmlspecialchars(strip_tags(trim((string)$data['phone']))) : null,
        ':address' => !empty($data['address']) ? htmlspecialchars(strip_tags(trim((string)$data['address']))) : null,
        ':user_id' => $targetUserId
    ]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('MODIFICATION_CLIENT', 'client', (int)$client['id'], $authUserId, [
        'email' => $data['email'] ?? null
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Profil client mis à jour']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
