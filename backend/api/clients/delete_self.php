<?php
/**
 * API: Suppression totale du compte client (par user_id)
 * DELETE /api/clients/delete_self.php
 * Body JSON: { user_id }
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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

    $stmt = $db->prepare("SELECT id, company_name FROM clients WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $data['user_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtClient = $db->prepare("DELETE FROM clients WHERE id = :id");
    $stmtClient->execute([':id' => $client['id']]);

    $stmtUser = $db->prepare("DELETE FROM users WHERE id = :user_id");
    $stmtUser->execute([':user_id' => $data['user_id']]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('SUPPRESSION_CLIENT', 'client', (int)$client['id'], (int)$data['user_id'], [
        'nom' => $client['company_name']
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Compte supprimé avec succès']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
