<?php
/**
 * API: Création d'une tâche
 * POST /api/tasks/create.php
 *
 * Cet endpoint permet à un administrateur ou à un employé de créer une tâche
 * liée à un événement. L'accès est protégé par JWT afin d'éviter qu'un
 * utilisateur non authentifié puisse créer une tâche directement via l'API.
 */

require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// La création d'une tâche est réservée aux rôles internes.
$currentUser = require_auth(['admin', 'employee']);

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format JSON invalide'
    ]);
    exit();
}

$event_id = $data['event_id'] ?? null;
$assigned_to = $data['assigned_to'] ?? null;
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$status = $data['status'] ?? 'pending';
$due_date = $data['due_date'] ?? null;

if (!$event_id || !$assigned_to || $title === '' || !$due_date) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Champs manquants'
    ]);
    exit();
}

// Nettoyage des champs texte avant insertion afin de limiter les contenus HTML non souhaités.
$title = htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars(strip_tags($description), ENT_QUOTES, 'UTF-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        INSERT INTO tasks (event_id, assigned_to, title, description, status, due_date)
        VALUES (:event_id, :assigned_to, :title, :description, :status, :due_date)
    ");

    $stmt->execute([
        ':event_id' => $event_id,
        ':assigned_to' => $assigned_to,
        ':title' => $title,
        ':description' => $description,
        ':status' => $status,
        ':due_date' => $due_date
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Tâche créée avec succès',
        'data' => [
            'id' => (int) $db->lastInsertId(),
            'event_id' => (int) $event_id,
            'assigned_to' => (int) $assigned_to,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'due_date' => $due_date
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}