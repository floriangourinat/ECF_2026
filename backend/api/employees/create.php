<?php
/**
 * API: Créer un employé
 * POST /api/employees/create.php
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

if (isset($data['role']) && $data['role'] === 'admin') {
    $errors[] = 'La création de compte administrateur est interdite depuis l\'application';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'email existe
    $stmtCheck = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmtCheck->execute([':email' => $data['email']]);
    if ($stmtCheck->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
        exit();
    }

    // Générer mot de passe temporaire
    $tempPassword = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

    // Créer l'utilisateur (role forcé à employee)
    $stmt = $db->prepare('
        INSERT INTO users (last_name, first_name, email, username, password, role, must_change_password, is_active, created_at)
        VALUES (:last_name, :first_name, :email, :username, :password, :role, 1, 1, NOW())
    ');

    $username = !empty($data['username']) ? $data['username'] : strtolower($data['first_name'] . '.' . $data['last_name']);

    $stmt->execute([
        ':last_name' => htmlspecialchars(strip_tags($data['last_name'])),
        ':first_name' => htmlspecialchars(strip_tags($data['first_name'])),
        ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
        ':username' => htmlspecialchars(strip_tags($username)),
        ':password' => $hashedPassword,
        ':role' => 'employee'
    ]);

    $employeeId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Employé créé avec succès',
        'data' => [
            'id' => $employeeId,
            'temp_password' => $tempPassword
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
