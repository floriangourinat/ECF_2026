<?php
/**
 * API: Modifier un employé
 * PUT /api/employees/update.php
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
    echo json_encode(['success' => false, 'message' => 'ID employé requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        UPDATE users 
        SET first_name = :first_name,
            last_name = :last_name,
            email = :email,
            username = :username,
            role = :role
        WHERE id = :id AND role IN ('admin', 'employee')
    ");

    $stmt->execute([
        ':id' => $data['id'],
        ':first_name' => htmlspecialchars(strip_tags($data['first_name'])),
        ':last_name' => htmlspecialchars(strip_tags($data['last_name'])),
        ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
        ':username' => htmlspecialchars(strip_tags($data['username'] ?? '')),
        ':role' => in_array($data['role'], ['admin', 'employee']) ? $data['role'] : 'employee'
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Employé modifié avec succès'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}