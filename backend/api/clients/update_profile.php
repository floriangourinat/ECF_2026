<?php
/**
 * API: Modifier profil client (par user_id)
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

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id");
    $stmtClient->execute([':user_id' => $data['user_id']]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtUser = $db->prepare("
        UPDATE users
        SET first_name = :first_name, last_name = :last_name, email = :email
        WHERE id = :user_id
    ");
    $stmtUser->execute([
        ':first_name' => $data['first_name'] ?? '',
        ':last_name' => $data['last_name'] ?? '',
        ':email' => $data['email'] ?? '',
        ':user_id' => $data['user_id']
    ]);

    $stmtUpdateClient = $db->prepare("
        UPDATE clients
        SET company_name = :company_name, phone = :phone, address = :address
        WHERE user_id = :user_id
    ");
    $stmtUpdateClient->execute([
        ':company_name' => $data['company_name'] ?? null,
        ':phone' => $data['phone'] ?? null,
        ':address' => $data['address'] ?? null,
        ':user_id' => $data['user_id']
    ]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('MODIFICATION_CLIENT', 'client', (int)$client['id'], (int)$data['user_id'], [
        'email' => $data['email'] ?? null
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Profil client mis à jour']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
