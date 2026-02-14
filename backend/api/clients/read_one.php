<?php
/**
 * API: Détail d'un client (admin/employee)
 * GET /api/clients/read_one.php?id=1
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

require_auth(['admin', 'employee']);

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID client requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT c.id, c.company_name, c.phone, c.address, c.created_at,
               u.id as user_id, u.first_name, u.last_name, u.email, u.is_active, u.username
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtEvents = $db->prepare("
        SELECT id, name, start_date, end_date, location, status
        FROM events
        WHERE client_id = :client_id
        ORDER BY start_date DESC
    ");
    $stmtEvents->execute([':client_id' => $_GET['id']]);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    $stmtQuotes = $db->prepare("
        SELECT q.id, q.total_ttc, q.status, q.created_at, e.name as event_name
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        WHERE e.client_id = :client_id
        ORDER BY q.created_at DESC
    ");
    $stmtQuotes->execute([':client_id' => $_GET['id']]);
    $quotes = $stmtQuotes->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'client' => $client,
            'events' => $events,
            'quotes' => $quotes
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
