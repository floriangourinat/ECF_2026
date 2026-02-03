<?php
/**
 * API: Détail d'un événement public
 * GET /api/events/read_one.php?id=1
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

    $stmt = $db->prepare("
        SELECT e.id, e.name, e.description, e.event_type, e.theme, e.location,
               e.start_date, e.end_date, e.image_url, e.status, e.participant_count,
               c.company_name as client_company
        FROM events e
        LEFT JOIN clients c ON e.client_id = c.id
        WHERE e.id = :id AND e.is_public = 1 AND e.status != 'brouillon'
    ");
    $stmt->execute([':id' => $_GET['id']]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $event
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}