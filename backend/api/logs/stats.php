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

    // Stats par action.
    $actionStats = [
        'CONNEXION_REUSSIE' => $logger->countLogs(['action' => 'CONNEXION_REUSSIE']),
        'CONNEXION_ECHOUEE' => $logger->countLogs(['action' => 'CONNEXION_ECHOUEE']),
        'CREATION_CLIENT' => $logger->countLogs(['action' => 'CREATION_CLIENT']),
        'MODIFICATION_CLIENT' => $logger->countLogs(['action' => 'MODIFICATION_CLIENT']),
        'SUPPRESSION_CLIENT' => $logger->countLogs(['action' => 'SUPPRESSION_CLIENT']),
        'CREATION_EVENEMENT' => $logger->countLogs(['action' => 'CREATION_EVENEMENT']),
        'MODIFICATION_STATUT_EVENEMENT' => $logger->countLogs(['action' => 'MODIFICATION_STATUT_EVENEMENT']),
        'GENERATION_DEVIS_PDF' => $logger->countLogs(['action' => 'GENERATION_DEVIS_PDF'])
    ];

    // Stats par entité
    $entityStats = [
        'user' => $logger->countLogs(['entity' => 'user']),
        'client' => $logger->countLogs(['entity' => 'client']),
        'event' => $logger->countLogs(['entity' => 'event']),
        'quote' => $logger->countLogs(['entity' => 'quote'])
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