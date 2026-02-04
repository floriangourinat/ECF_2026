<?php
/**
 * API: Détail d'un prospect
 * GET /api/prospects/read_one.php?id=1
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
    echo json_encode(['success' => false, 'message' => 'ID prospect requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM prospects WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);

    $prospect = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prospect) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Prospect non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $prospect
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}