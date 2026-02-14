<?php
/**
 * API: Supprimer un devis
 * DELETE /api/quotes/delete.php
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
    echo json_encode(['success' => false, 'message' => 'ID devis requis']);
    exit();
}

$quoteId = (int)$data['id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtCheck = $db->prepare('SELECT id, status FROM quotes WHERE id = :id LIMIT 1');
    $stmtCheck->execute([':id' => $quoteId]);
    $quote = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    $stmt = $db->prepare('DELETE FROM quotes WHERE id = :id');
    $stmt->execute([':id' => $quoteId]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('SUPPRESSION_DEVIS', 'quote', $quoteId, $userId, [
        'ancien_statut' => $quote['status']
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Devis supprimé avec succès'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
