<?php
/**
 * API: Détail client par user_id
 * GET /api/clients/read_by_user.php?user_id=1
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

if (empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT c.id as client_id, c.company_name, c.phone, c.address, c.created_at,
               u.id as user_id, u.first_name, u.last_name, u.email, u.username, u.is_active
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $_GET['user_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $client
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
