<?php
/**
 * API: Modifier un client
 * PUT /api/clients/update.php
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

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID client requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer le client
    $stmtCheck = $db->prepare("SELECT user_id FROM clients WHERE id = :id");
    $stmtCheck->execute([':id' => $data['id']]);
    $client = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $db->beginTransaction();

    // Mettre à jour l'utilisateur
    $stmtUser = $db->prepare("
        UPDATE users 
        SET last_name = :last_name, first_name = :first_name, email = :email
        WHERE id = :user_id
    ");
    $stmtUser->execute([
        ':last_name' => htmlspecialchars(strip_tags($data['last_name'])),
        ':first_name' => htmlspecialchars(strip_tags($data['first_name'])),
        ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
        ':user_id' => $client['user_id']
    ]);

    // Mettre à jour le client
    $stmtClient = $db->prepare("
        UPDATE clients 
        SET company_name = :company_name, phone = :phone, address = :address
        WHERE id = :id
    ");
    $stmtClient->execute([
        ':company_name' => !empty($data['company_name']) ? htmlspecialchars(strip_tags($data['company_name'])) : null,
        ':phone' => !empty($data['phone']) ? htmlspecialchars(strip_tags($data['phone'])) : null,
        ':address' => !empty($data['address']) ? htmlspecialchars(strip_tags($data['address'])) : null,
        ':id' => $data['id']
    ]);

    $db->commit();

    // Log MongoDB - Client modifié
    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('update', 'client', (int)$data['id'], null, [
        'company_name' => $data['company_name'] ?? null,
        'email' => $data['email']
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Client modifié avec succès'
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}