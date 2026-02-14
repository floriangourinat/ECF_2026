<?php
/**
 * API: Liste des devis d'un client (par user_id)
 * GET /api/quotes/read_by_client.php?user_id=1
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['admin', 'client']);
$authUserId = (int)$payload['user_id'];
$role = $payload['role'] ?? '';

$userId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

if ($role === 'client' && $userId !== $authUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare('SELECT id FROM clients WHERE user_id = :user_id LIMIT 1');
    $stmtClient->execute([':user_id' => $userId]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtHasCounterProposal = $db->query("SHOW COLUMNS FROM quotes LIKE 'counter_proposal'");
    $hasCounterProposal = (bool)$stmtHasCounterProposal->fetch(PDO::FETCH_ASSOC);

    $counterProposalSelect = $hasCounterProposal
        ? 'q.counter_proposal, q.counter_proposed_at,'
        : 'NULL AS counter_proposal, NULL AS counter_proposed_at,';

    $stmt = $db->prepare(
        "SELECT q.id, q.event_id, q.total_ht, q.tax_rate, q.total_ttc, q.status, q.issue_date, q.created_at,
                q.modification_reason,
                {$counterProposalSelect}
                e.name as event_name, e.start_date as event_date, e.location as event_location
         FROM quotes q
         JOIN events e ON q.event_id = e.id
         WHERE e.client_id = :client_id
           AND q.status != 'draft'
         ORDER BY q.created_at DESC"
    );
    $stmt->execute([':client_id' => (int)$client['id']]);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($quotes),
        'data' => $quotes
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
