<?php
/**
 * API: Liste des prospects
 * GET /api/prospects/read.php
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

    $sql = "SELECT * FROM prospects WHERE 1=1";
    $params = [];

    // Filtre par statut
    if (!empty($_GET['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }

    // Recherche
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (company_name LIKE :search OR last_name LIKE :search2 OR first_name LIKE :search3 OR email LIKE :search4)";
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
        $params[':search4'] = $search;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($prospects),
        'data' => $prospects
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}