<?php
/**
 * API: Statistiques des logs
 * GET /api/logs/stats.php
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
require_once '../../config/database.php';

try {
    $logger = new MongoLogger();
    $database = new Database();
    $db = $database->getConnection();

    // Stats par action
    $actionStats = [
        'create' => $logger->countLogs(['action' => 'create']),
        'update' => $logger->countLogs(['action' => 'update']),
        'delete' => $logger->countLogs(['action' => 'delete']),
        'login' => $logger->countLogs(['action' => 'login']),
        'logout' => $logger->countLogs(['action' => 'logout'])
    ];

    // Stats par entité
    $entityStats = [
        'user' => $logger->countLogs(['entity' => 'user']),
        'client' => $logger->countLogs(['entity' => 'client']),
        'event' => $logger->countLogs(['entity' => 'event']),
        'quote' => $logger->countLogs(['entity' => 'quote']),
        'prospect' => $logger->countLogs(['entity' => 'prospect']),
        'review' => $logger->countLogs(['entity' => 'review'])
    ];

    // Récupérer les noms des utilisateurs pour les logs récents
    $stmtUsers = $db->query("SELECT id, first_name, last_name FROM users");
    $users = [];
    while ($row = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
        $users[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $logger->countLogs(),
            'by_action' => $actionStats,
            'by_entity' => $entityStats,
            'users' => $users
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}