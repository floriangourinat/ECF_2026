<?php
/**
 * API: Liste des clients
 * GET /api/clients/read.php
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

    $sql = "SELECT c.id, c.company_name, c.phone, c.address, c.created_at,
                   u.id as user_id, u.first_name, u.last_name, u.email, u.is_active,
                   COUNT(e.id) as events_count
            FROM clients c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN events e ON e.client_id = c.id
            WHERE 1=1";
    
    $params = [];

    // Recherche
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (c.company_name LIKE :search OR u.last_name LIKE :search2 OR u.first_name LIKE :search3 OR u.email LIKE :search4)";
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
        $params[':search4'] = $search;
    }

    // Filtre actif/inactif
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $sql .= " AND u.is_active = :is_active";
        $params[':is_active'] = $_GET['is_active'];
    }

    $sql .= " GROUP BY c.id, c.company_name, c.phone, c.address, c.created_at, u.id, u.first_name, u.last_name, u.email, u.is_active ORDER BY c.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($clients),
        'data' => $clients
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
