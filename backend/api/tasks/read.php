<?php
/**
 * API: Liste des tâches
 * GET /api/tasks/read.php?assigned_to=1&event_id=2
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT t.*, e.name as event_name, 
                   u.first_name as assigned_first_name, u.last_name as assigned_last_name
            FROM tasks t
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE 1=1";
    $params = [];

    if (!empty($_GET['assigned_to'])) {
        $sql .= " AND t.assigned_to = :assigned_to";
        $params[':assigned_to'] = (int)$_GET['assigned_to'];
    }

    if (!empty($_GET['event_id'])) {
        $sql .= " AND t.event_id = :event_id";
        $params[':event_id'] = (int)$_GET['event_id'];
    }

    $sql .= " ORDER BY t.due_date ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $tasks]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
