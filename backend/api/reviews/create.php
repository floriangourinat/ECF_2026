<?php
/**
 * API: Créer un avis client
 * POST /api/reviews/create.php
 * Body JSON: { user_id, event_id, rating, comment }
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['user_id']) || empty($data['event_id']) || empty($data['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtClient = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id");
    $stmtClient->execute([':user_id' => $data['user_id']]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtEvent = $db->prepare("SELECT id, status FROM events WHERE id = :event_id AND client_id = :client_id");
    $stmtEvent->execute([
        ':event_id' => $data['event_id'],
        ':client_id' => $client['id']
    ]);
    $event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    if ($event['status'] !== 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Événement non terminé']);
        exit();
    }

    $stmtReview = $db->prepare("
        INSERT INTO reviews (client_id, event_id, rating, comment, status, created_at)
        VALUES (:client_id, :event_id, :rating, :comment, 'pending', NOW())
    ");
    $stmtReview->execute([
        ':client_id' => $client['id'],
        ':event_id' => $data['event_id'],
        ':rating' => $data['rating'],
        ':comment' => $data['comment'] ?? null
    ]);

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Avis envoyé pour modération']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
