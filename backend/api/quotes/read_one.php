<?php
/**
 * API: Détail d'un devis
 * GET /api/quotes/read_one.php?id=1
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

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID devis requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Infos devis
    $stmt = $db->prepare("
        SELECT q.*, e.name as event_name, e.start_date as event_date, e.location as event_location,
               c.company_name, c.phone as client_phone, c.address as client_address,
               u.first_name, u.last_name, u.email as client_email
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        LEFT JOIN clients c ON e.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE q.id = :id
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    // Prestations du devis
    $stmtServices = $db->prepare("
        SELECT * FROM services WHERE quote_id = :quote_id ORDER BY id ASC
    ");
    $stmtServices->execute([':quote_id' => $_GET['id']]);
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'quote' => $quote,
            'services' => $services
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}