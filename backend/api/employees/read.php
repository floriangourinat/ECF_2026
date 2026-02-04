<?php
/**
 * API: Liste des employés
 * GET /api/employees/read.php
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

    $sql = "SELECT id, first_name, last_name, email, username, role, is_active, created_at
            FROM users 
            WHERE role IN ('admin', 'employee')";
    
    $params = [];

    // Filtre par rôle
    if (!empty($_GET['role'])) {
        $sql = "SELECT id, first_name, last_name, email, username, role, is_active, created_at
                FROM users 
                WHERE role = :role";
        $params[':role'] = $_GET['role'];
    }

    // Recherche
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (first_name LIKE :search OR last_name LIKE :search2 OR email LIKE :search3)";
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($employees),
        'data' => $employees
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}