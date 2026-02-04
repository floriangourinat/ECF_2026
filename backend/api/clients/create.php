<?php
/**
 * API: Créer un client
 * POST /api/clients/create.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validation
$errors = [];
if (empty($data['email'])) $errors[] = 'Email requis';
if (empty($data['last_name'])) $errors[] = 'Nom requis';
if (empty($data['first_name'])) $errors[] = 'Prénom requis';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'email existe
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmtCheck->execute([':email' => $data['email']]);
    if ($stmtCheck->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
        exit();
    }

    $db->beginTransaction();

    // Générer mot de passe temporaire
    $tempPassword = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

    // Créer l'utilisateur
    $stmtUser = $db->prepare("
        INSERT INTO users (last_name, first_name, email, password, role, must_change_password, created_at)
        VALUES (:last_name, :first_name, :email, :password, 'client', 1, NOW())
    ");
    $stmtUser->execute([
        ':last_name' => htmlspecialchars(strip_tags($data['last_name'])),
        ':first_name' => htmlspecialchars(strip_tags($data['first_name'])),
        ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
        ':password' => $hashedPassword
    ]);
    $userId = $db->lastInsertId();

    // Créer le client
    $stmtClient = $db->prepare("
        INSERT INTO clients (user_id, company_name, phone, address, created_at)
        VALUES (:user_id, :company_name, :phone, :address, NOW())
    ");
    $stmtClient->execute([
        ':user_id' => $userId,
        ':company_name' => !empty($data['company_name']) ? htmlspecialchars(strip_tags($data['company_name'])) : null,
        ':phone' => !empty($data['phone']) ? htmlspecialchars(strip_tags($data['phone'])) : null,
        ':address' => !empty($data['address']) ? htmlspecialchars(strip_tags($data['address'])) : null
    ]);
    $clientId = $db->lastInsertId();

    $db->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Client créé avec succès',
        'data' => [
            'client_id' => $clientId,
            'user_id' => $userId,
            'temp_password' => $tempPassword
        ]
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}