<?php
/**
 * API: Liste des événements publics
 * GET /api/events/read_public.php
 * Filtres optionnels: ?type=seminaire&theme=corporate&date_start=2026-01-01&date_end=2026-12-31
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

    // Construction de la requête avec filtres
    $sql = "SELECT e.id, e.name, e.description, e.event_type, e.theme, e.location,
                   e.start_date, e.end_date, e.image_url, e.status,
                   c.company_name as client_company
            FROM events e
            LEFT JOIN clients c ON e.client_id = c.id
            WHERE e.is_public = 1 AND e.status != 'brouillon'";
    
    $params = [];

    // Filtre par type d'événement
    if (!empty($_GET['type'])) {
        $sql .= " AND e.event_type = :type";
        $params[':type'] = $_GET['type'];
    }

    // Filtre par thème
    if (!empty($_GET['theme'])) {
        $sql .= " AND e.theme = :theme";
        $params[':theme'] = $_GET['theme'];
    }

    // Filtre par plage de dates
    if (!empty($_GET['date_start'])) {
        $sql .= " AND e.start_date >= :date_start";
        $params[':date_start'] = $_GET['date_start'];
    }

    if (!empty($_GET['date_end'])) {
        $sql .= " AND e.end_date <= :date_end";
        $params[':date_end'] = $_GET['date_end'];
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
        'message' => 'Erreur serveur'
    ]);
}