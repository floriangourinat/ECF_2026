<?php
/**
 * API: Mettre à jour le statut d'un devis
 * PUT /api/quotes/update_status.php
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

$payload = require_auth(['admin']);
$userId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit();
}

$quoteId = (int)$data['id'];
$newStatus = trim((string)$data['status']);
$validStatuses = ['draft', 'pending', 'modification', 'accepted', 'refused'];
if (!in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtCurrent = $db->prepare('SELECT id, status FROM quotes WHERE id = :id LIMIT 1');
    $stmtCurrent->execute([':id' => $quoteId]);
    $currentQuote = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

    if (!$currentQuote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    $sql = 'UPDATE quotes SET status = :status, updated_at = NOW()';
    $params = [':status' => $newStatus, ':id' => $quoteId];

    if (!empty($data['modification_reason'])) {
        $sql .= ', modification_reason = :reason';
        $params[':reason'] = htmlspecialchars(strip_tags((string)$data['modification_reason']));
    }

    $sql .= ' WHERE id = :id';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('MODIFICATION_STATUT_DEVIS', 'quote', $quoteId, $userId, [
        'ancien_statut' => $currentQuote['status'],
        'nouveau_statut' => $newStatus
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
