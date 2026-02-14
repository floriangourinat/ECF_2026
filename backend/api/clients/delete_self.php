<?php
/**
 * API: Suppression totale du compte client
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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['client']);
$authUserId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

$targetUserId = (int)$data['user_id'];
if ($targetUserId !== $authUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT id, company_name FROM clients WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $targetUserId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $db->beginTransaction();

    $stmtClient = $db->prepare('DELETE FROM clients WHERE id = :id');
    $stmtClient->execute([':id' => (int)$client['id']]);

    $stmtUser = $db->prepare('DELETE FROM users WHERE id = :user_id');
    $stmtUser->execute([':user_id' => $targetUserId]);

    $db->commit();

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('SUPPRESSION_CLIENT', 'client', (int)$client['id'], $authUserId, [
        'nom' => $client['company_name']
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Compte supprimé avec succès']);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
