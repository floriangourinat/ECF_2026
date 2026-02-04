<?php
/**
 * API: Supprimer un client
 * DELETE /api/clients/delete.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

    // Récupérer le user_id
    $stmtClient = $db->prepare("SELECT user_id FROM clients WHERE id = :id");
    $stmtClient->execute([':id' => $data['id']]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit();
    }

    $db->beginTransaction();

    // Supprimer le client (cascade supprimera les événements liés)
    $stmtDeleteClient = $db->prepare("DELETE FROM clients WHERE id = :id");
    $stmtDeleteClient->execute([':id' => $data['id']]);

    // Supprimer l'utilisateur
    $stmtDeleteUser = $db->prepare("DELETE FROM users WHERE id = :user_id");
    $stmtDeleteUser->execute([':user_id' => $client['user_id']]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Client supprimé avec succès'
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}