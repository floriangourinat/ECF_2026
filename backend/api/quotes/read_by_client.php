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

require_once '../../config/database.php';

if (empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id");
    $stmtClient->execute([':user_id' => $_GET['user_id']]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmt = $db->prepare("
        SELECT q.id, q.event_id, q.total_ht, q.tax_rate, q.total_ttc, q.status, q.issue_date, q.created_at,
               q.modification_reason,
               e.name as event_name, e.start_date as event_date, e.location as event_location
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        WHERE e.client_id = :client_id
          AND q.status != 'draft'
        ORDER BY q.created_at DESC
    ");
    $stmt->execute([':client_id' => $client['id']]);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($quotes),
        'data' => $quotes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
