<?php
/**
 * API: Liste de tous les événements (admin)
 * GET /api/events/read_all.php
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

    $sql = "SELECT e.*, c.company_name as client_company,
                   u.first_name as client_first_name, u.last_name as client_last_name
            FROM events e
            LEFT JOIN clients c ON e.client_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];

    // Filtre par statut
    if (!empty($_GET['status'])) {
        $sql .= " AND e.status = :status";
        $params[':status'] = $_GET['status'];
    }

    // Filtre par client
    if (!empty($_GET['client_id'])) {
        $sql .= " AND e.client_id = :client_id";
        $params[':client_id'] = $_GET['client_id'];
    }

    // Recherche
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (e.name LIKE :search OR e.location LIKE :search2 OR c.company_name LIKE :search3)";
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
    }

    $sql .= " ORDER BY e.start_date DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($events),
        'data' => $events
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}