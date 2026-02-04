<?php
/**
 * API: Détail complet d'un événement (admin)
 * GET /api/events/read_detail.php?id=1
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
    echo json_encode(['success' => false, 'message' => 'ID événement requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Infos événement
    $stmt = $db->prepare("
        SELECT e.*, c.company_name as client_company, c.id as client_id,
               u.first_name as client_first_name, u.last_name as client_last_name, u.email as client_email
        FROM events e
        LEFT JOIN clients c ON e.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE e.id = :id
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    // Devis associés
    $stmtQuotes = $db->prepare("
        SELECT * FROM quotes WHERE event_id = :event_id ORDER BY created_at DESC
    ");
    $stmtQuotes->execute([':event_id' => $_GET['id']]);
    $quotes = $stmtQuotes->fetchAll(PDO::FETCH_ASSOC);

    // Notes associées
    $stmtNotes = $db->prepare("
        SELECT n.*, u.first_name, u.last_name
        FROM notes n
        JOIN users u ON n.author_id = u.id
        WHERE n.event_id = :event_id
        ORDER BY n.created_at DESC
    ");
    $stmtNotes->execute([':event_id' => $_GET['id']]);
    $notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

    // Tâches associées
    $stmtTasks = $db->prepare("
        SELECT t.*, u.first_name as assigned_first_name, u.last_name as assigned_last_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.event_id = :event_id
        ORDER BY t.due_date ASC
    ");
    $stmtTasks->execute([':event_id' => $_GET['id']]);
    $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'event' => $event,
            'quotes' => $quotes,
            'notes' => $notes,
            'tasks' => $tasks
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}