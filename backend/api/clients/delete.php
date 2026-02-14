<?php
/**
 * API: Supprimer un client
 * DELETE /api/clients/delete.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['admin']);
$userId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID client requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtCheck = $db->prepare('SELECT c.user_id, c.company_name FROM clients c WHERE c.id = :id LIMIT 1');
    $stmtCheck->execute([':id' => (int)$data['id']]);
    $client = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $db->beginTransaction();

    $stmtClient = $db->prepare('DELETE FROM clients WHERE id = :id');
    $stmtClient->execute([':id' => (int)$data['id']]);

    $stmtUser = $db->prepare('DELETE FROM users WHERE id = :user_id');
    $stmtUser->execute([':user_id' => (int)$client['user_id']]);

    $db->commit();

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('SUPPRESSION_CLIENT', 'client', (int)$data['id'], $userId, [
        'id' => (int)$data['id'],
        'nom' => $client['company_name']
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Client supprimé avec succès'
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
