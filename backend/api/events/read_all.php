<?php
/**
 * API: Liste de tous les événements (admin/employee)
 * GET /api/events/read_all.php
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

$payload = require_auth(['admin', 'employee', 'client']);

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT e.*, c.company_name as client_company,
                   u.first_name as client_first_name, u.last_name as client_last_name
            FROM events e
            LEFT JOIN clients c ON e.client_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE 1=1";

    $params = [];

    if (!empty($_GET['status'])) {
        $sql .= " AND e.status = :status";
        $params[':status'] = $_GET['status'];
    }

    if (($payload['role'] ?? null) === 'client') {
        $stmtClient = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id LIMIT 1");
        $stmtClient->execute([':user_id' => (int)$payload['user_id']]);
        $clientRow = $stmtClient->fetch(PDO::FETCH_ASSOC);

        if (!$clientRow || empty($clientRow['id'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Profil client introuvable']);
            exit();
        }

        $sql .= " AND e.client_id = :client_id";
        $params[':client_id'] = (int)$clientRow['id'];
    } else {
        if (!empty($_GET['client_id'])) {
            $sql .= " AND e.client_id = :client_id";
            $params[':client_id'] = $_GET['client_id'];
        }
    }

    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (e.name LIKE :search OR e.location LIKE :search2 OR c.company_name LIKE :search3)";
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
    }

    $sql .= " ORDER BY e.start_date DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($events),
        'data' => $events
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
