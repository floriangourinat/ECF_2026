<?php
/**
 * API: Liste des avis du client connecté
 * GET /api/reviews/read_by_client.php?user_id=1
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
require_once '../../middleware/auth.php';

$payload = require_auth(['admin', 'client']);
$authUserId = (int)$payload['user_id'];
$role = $payload['role'] ?? '';

$userId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit();
}

if ($role === 'client' && $userId !== $authUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare('SELECT id FROM clients WHERE user_id = :user_id LIMIT 1');
    $stmtClient->execute([':user_id' => $userId]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmt = $db->prepare(
        'SELECT r.id, r.event_id, r.rating, r.comment, r.status, r.created_at,
                e.name AS event_name, e.start_date, e.end_date, e.location
         FROM reviews r
         INNER JOIN events e ON r.event_id = e.id
         WHERE r.client_id = :client_id
         ORDER BY r.created_at DESC'
    );
    $stmt->execute([':client_id' => (int)$client['id']]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($reviews),
        'data' => $reviews
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
