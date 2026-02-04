<?php
/**
 * API: Liste des devis
 * GET /api/quotes/read.php
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

    $sql = "SELECT q.*, e.name as event_name, e.start_date as event_date,
                   c.company_name as client_company, u.first_name, u.last_name
            FROM quotes q
            JOIN events e ON q.event_id = e.id
            LEFT JOIN clients c ON e.client_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];

    // Filtre par statut
    if (!empty($_GET['status'])) {
        $sql .= " AND q.status = :status";
        $params[':status'] = $_GET['status'];
    }

    // Filtre par événement
    if (!empty($_GET['event_id'])) {
        $sql .= " AND q.event_id = :event_id";
        $params[':event_id'] = $_GET['event_id'];
    }

    $sql .= " ORDER BY q.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($quotes),
        'data' => $quotes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}