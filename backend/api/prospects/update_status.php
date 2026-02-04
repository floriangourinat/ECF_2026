<?php
/**
 * API: Mettre à jour le statut d'un prospect
 * PUT /api/prospects/update_status.php
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

if (empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit();
}

$validStatuses = ['to_contact', 'qualification', 'failed', 'converted'];
if (!in_array($data['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("UPDATE prospects SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $data['status'],
        ':id' => $data['id']
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Prospect non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}