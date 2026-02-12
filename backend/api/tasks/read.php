<?php
/**
 * API: Lire les tâches
 * GET /api/tasks/read.php
 *
 * Paramètres optionnels:
 * - event_id: filtrer par événement
 * - assigned_to: filtrer par utilisateur assigné
 * - status: filtrer par statut
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

try {
    $database = new Database();
    $db = $database->getConnection();

    $conditions = [];
    $params = [];

    if (!empty($_GET['event_id'])) {
        $conditions[] = 't.event_id = :event_id';
        $params[':event_id'] = (int)$_GET['event_id'];
    }

    if (!empty($_GET['assigned_to'])) {
        $conditions[] = 't.assigned_to = :assigned_to';
        $params[':assigned_to'] = (int)$_GET['assigned_to'];
    }

    if (!empty($_GET['status'])) {
        $conditions[] = 't.status = :status';
        $params[':status'] = $_GET['status'];
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $stmt = $db->prepare("
        SELECT t.*, 
               e.name as event_name,
               u.first_name as assigned_first_name,
               u.last_name as assigned_last_name
        FROM tasks t
        LEFT JOIN events e ON t.event_id = e.id
        LEFT JOIN users u ON t.assigned_to = u.id
        $whereClause
        ORDER BY
            CASE t.status
                WHEN 'in_progress' THEN 1
                WHEN 'todo' THEN 2
                WHEN 'done' THEN 3
                ELSE 4
            END,
            t.due_date IS NULL,
            t.due_date ASC,
            t.created_at DESC
    ");

    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($tasks),
        'data' => $tasks
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
