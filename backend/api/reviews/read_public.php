<?php
/**
 * API: Liste des avis validés
 * GET /api/reviews/read_public.php
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

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at,
               c.company_name, u.first_name, u.last_name,
               e.name as event_name, e.event_type
        FROM reviews r
        JOIN clients c ON r.client_id = c.id
        JOIN users u ON c.user_id = u.id
        JOIN events e ON r.event_id = e.id
        WHERE r.status = 'approved'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($reviews),
        'data' => $reviews
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}