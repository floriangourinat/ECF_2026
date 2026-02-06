<?php
/**
 * API: Liste des logs
 * GET /api/logs/read.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../services/MongoLogger.php';

try {
    $logger = new MongoLogger();

    $filters = [];
    if (!empty($_GET['action'])) $filters['action'] = $_GET['action'];
    if (!empty($_GET['entity'])) $filters['entity'] = $_GET['entity'];
    if (!empty($_GET['user_id'])) $filters['user_id'] = $_GET['user_id'];
    if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
    if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];

    $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $skip = !empty($_GET['skip']) ? (int)$_GET['skip'] : 0;

    $logs = $logger->getLogs($filters, $limit, $skip);
    $total = $logger->countLogs($filters);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($logs),
        'total' => $total,
        'data' => $logs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}