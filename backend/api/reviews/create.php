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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON invalide']);
    exit();
}

$userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$eventId = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$rating = filter_var($data['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$comment = isset($data['comment']) ? trim((string)$data['comment']) : null;

if (!$userId || !$eventId || !$rating) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données incomplètes ou invalides']);
    exit();
}

if ($comment !== null && mb_strlen($comment) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas dépasser 1000 caractères']);
    exit();
}

if ($comment === '') {
    $comment = null;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $stmtClient = $db->prepare('SELECT id FROM clients WHERE user_id = :user_id LIMIT 1');
    $stmtClient->execute([':user_id' => $userId]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $stmtEvent = $db->prepare('SELECT id, status FROM events WHERE id = :event_id AND client_id = :client_id LIMIT 1');
    $stmtEvent->execute([
        ':event_id' => $eventId,
        ':client_id' => (int)$client['id']
    ]);
    $event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    if ($event['status'] !== 'completed') {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Événement non terminé']);
        exit();
    }

    $stmtExisting = $db->prepare('SELECT id, status, rating, comment, created_at FROM reviews WHERE client_id = :client_id AND event_id = :event_id LIMIT 1 FOR UPDATE');
    $stmtExisting->execute([
        ':client_id' => (int)$client['id'],
        ':event_id' => $eventId
    ]);
    $existingReview = $stmtExisting->fetch(PDO::FETCH_ASSOC);

    if ($existingReview) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Vous avez déjà laissé un avis pour cet événement.',
            'data' => $existingReview
        ]);
        exit();
    }

    $stmtReview = $db->prepare('INSERT INTO reviews (client_id, event_id, rating, comment, status, created_at) VALUES (:client_id, :event_id, :rating, :comment, :status, NOW())');
    $stmtReview->execute([
        ':client_id' => (int)$client['id'],
        ':event_id' => $eventId,
        ':rating' => $rating,
        ':comment' => $comment,
        ':status' => 'pending'
    ]);

    $reviewId = (int)$db->lastInsertId();
    $db->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Avis envoyé pour modération',
        'data' => [
            'id' => $reviewId,
            'event_id' => $eventId,
            'rating' => $rating,
            'comment' => $comment,
            'status' => 'pending'
        ]
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Vous avez déjà laissé un avis pour cet événement.'
        ]);
        exit();
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
