<?php
/**
 * API: Détail d'un employé
 * GET /api/employees/read_one.php?id=1
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

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID employé requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Infos employé
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email, username, role, is_active, created_at
        FROM users 
        WHERE id = :id AND role IN ('admin', 'employee')
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
        exit();
    }

    // Tâches assignées
    $stmtTasks = $db->prepare("
        SELECT t.*, e.name as event_name
        FROM tasks t
        JOIN events e ON t.event_id = e.id
        WHERE t.assigned_to = :user_id
        ORDER BY t.due_date ASC
    ");
    $stmtTasks->execute([':user_id' => $_GET['id']]);
    $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

    // Notes créées
    $stmtNotes = $db->prepare("
        SELECT n.*, e.name as event_name
        FROM notes n
        LEFT JOIN events e ON n.event_id = e.id
        WHERE n.author_id = :user_id
        ORDER BY n.created_at DESC
        LIMIT 10
    ");
    $stmtNotes->execute([':user_id' => $_GET['id']]);
    $notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'employee' => $employee,
            'tasks' => $tasks,
            'notes' => $notes
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}