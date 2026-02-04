<?php
/**
 * API: Activer/Désactiver un client
 * PUT /api/clients/toggle_status.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID client requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer le user_id du client
    $stmtClient = $db->prepare("SELECT user_id FROM clients WHERE id = :id");
    $stmtClient->execute([':id' => $data['id']]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    // Toggle le statut
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :user_id");
    $stmt->execute([':user_id' => $client['user_id']]);

    // Récupérer le nouveau statut
    $stmtStatus = $db->prepare("SELECT is_active FROM users WHERE id = :user_id");
    $stmtStatus->execute([':user_id' => $client['user_id']]);
    $newStatus = $stmtStatus->fetch(PDO::FETCH_ASSOC)['is_active'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $newStatus ? 'Client activé' : 'Client suspendu',
        'data' => ['is_active' => (bool)$newStatus]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}