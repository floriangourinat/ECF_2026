<?php
/**
 * API: Créer une tâche
 * POST /api/tasks/create.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

require_auth(['admin']);

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['event_id']) || empty($data['title'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'event_id et title sont requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('\n        INSERT INTO tasks (event_id, assigned_to, title, description, status, due_date)\n        VALUES (:event_id, :assigned_to, :title, :description, :status, :due_date)\n    ');

    $stmt->execute([
        ':event_id' => (int)$data['event_id'],
        ':assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
        ':title' => htmlspecialchars(strip_tags(trim($data['title']))),
        ':description' => !empty($data['description']) ? htmlspecialchars(strip_tags(trim($data['description']))) : null,
        ':status' => $data['status'] ?? 'todo',
        ':due_date' => !empty($data['due_date']) ? $data['due_date'] : null
    ]);

    $taskId = (int)$db->lastInsertId();

    $stmtRead = $db->prepare('\n        SELECT t.*, u.first_name as assigned_first_name, u.last_name as assigned_last_name\n        FROM tasks t\n        LEFT JOIN users u ON t.assigned_to = u.id\n        WHERE t.id = :id\n    ');
    $stmtRead->execute([':id' => $taskId]);
    $task = $stmtRead->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Tâche créée', 'data' => $task]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
