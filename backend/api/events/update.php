<?php
/**
 * API: Modifier un événement
 * PUT /api/events/update.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID événement requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        UPDATE events 
        SET name = :name,
            client_id = :client_id,
            start_date = :start_date,
            end_date = :end_date,
            location = :location,
            event_type = :event_type,
            theme = :theme,
            status = :status,
            is_visible = :is_visible,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $data['id'],
        ':name' => htmlspecialchars(strip_tags($data['name'])),
        ':client_id' => $data['client_id'],
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'],
        ':location' => !empty($data['location']) ? htmlspecialchars(strip_tags($data['location'])) : null,
        ':event_type' => !empty($data['event_type']) ? $data['event_type'] : null,
        ':theme' => !empty($data['theme']) ? $data['theme'] : null,
        ':status' => !empty($data['status']) ? $data['status'] : 'draft',
        ':is_visible' => !empty($data['is_visible']) ? 1 : 0
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Événement modifié avec succès'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}