<?php
/**
 * API: Liste de tous les avis (admin/employee)
 * GET /api/reviews/read_all.php
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

require_auth(['admin', 'employee']);

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT r.*, e.name as event_name,
                   c.company_name, u.first_name, u.last_name, u.email
            FROM reviews r
            JOIN events e ON r.event_id = e.id
            JOIN clients c ON r.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE 1=1";

    $params = [];

    if (!empty($_GET['status'])) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $_GET['status'];
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($reviews),
        'data' => $reviews
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
