<?php
/**
 * API: Créer un événement
 * POST /api/events/create.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validation
$errors = [];
if (empty($data['name'])) $errors[] = 'Nom requis';
if (empty($data['start_date'])) $errors[] = 'Date de début requise';
if (empty($data['end_date'])) $errors[] = 'Date de fin requise';
if (empty($data['client_id'])) $errors[] = 'Client requis';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        INSERT INTO events (client_id, name, start_date, end_date, location, event_type, theme, status, is_visible, created_at)
        VALUES (:client_id, :name, :start_date, :end_date, :location, :event_type, :theme, :status, :is_visible, NOW())
    ");

    $stmt->execute([
        ':client_id' => $data['client_id'],
        ':name' => htmlspecialchars(strip_tags($data['name'])),
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'],
        ':location' => !empty($data['location']) ? htmlspecialchars(strip_tags($data['location'])) : null,
        ':event_type' => !empty($data['event_type']) ? $data['event_type'] : null,
        ':theme' => !empty($data['theme']) ? $data['theme'] : null,
        ':status' => !empty($data['status']) ? $data['status'] : 'draft',
        ':is_visible' => !empty($data['is_visible']) ? 1 : 0
    ]);

    $eventId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Événement créé avec succès',
        'data' => ['id' => $eventId]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}