<?php
/**
 * API: Modifier le statut d'une tâche
 * POST ou PUT /api/tasks/update_status.php
 * Sécurité : seul l'employé assigné ou un admin peut modifier le statut
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['admin', 'employee']);
$userId = (int)$payload['user_id'];
$userRole = $payload['role'] ?? '';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit();
}

$allowedStatuses = ['todo', 'in_progress', 'done'];
if (!in_array($data['status'], $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtCheck = $db->prepare('SELECT id, assigned_to, status FROM tasks WHERE id = :id');
    $stmtCheck->execute([':id' => (int)$data['id']]);
    $task = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
        exit();
    }

    $isAdmin = $userRole === 'admin';
    $isAssigned = (int)$task['assigned_to'] === $userId;

    if (!$isAdmin && !$isAssigned) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez modifier que vos propres tâches']);
        exit();
    }

    $validTransitions = [
        'todo' => 'in_progress',
        'in_progress' => 'done'
    ];

    if (!$isAdmin && isset($validTransitions[$task['status']]) && $validTransitions[$task['status']] !== $data['status']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transition de statut invalide']);
        exit();
    }

    $stmt = $db->prepare('UPDATE tasks SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $data['status'], ':id' => (int)$data['id']]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
