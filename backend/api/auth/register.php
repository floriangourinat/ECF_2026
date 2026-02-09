<?php
/**
 * API Endpoint: Inscription utilisateur
 * POST /api/auth/register.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Méthode non autorisée"]);
    exit;
}

include_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

// Validation des champs requis
$errors = [];
if (empty($data->email)) $errors[] = "Email requis";
if (empty($data->password)) $errors[] = "Mot de passe requis";
if (empty($data->last_name)) $errors[] = "Nom requis";
if (empty($data->first_name)) $errors[] = "Prénom requis";
if (empty($data->username)) $errors[] = "Nom d'utilisateur requis";

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["message" => "Données incomplètes.", "errors" => $errors]);
    exit;
}

// Validation email
$email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

// Validation mot de passe (min 8 caractères, 1 majuscule, 1 chiffre)
if (strlen($data->password) < 8) {
    http_response_code(400);
    echo json_encode(["message" => "Le mot de passe doit contenir au moins 8 caractères."]);
    exit;
}

if (!preg_match('/[A-Z]/', $data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Le mot de passe doit contenir au moins une majuscule."]);
    exit;
}

if (!preg_match('/[0-9]/', $data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Le mot de passe doit contenir au moins un chiffre."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'email existe déjà
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Un compte avec cet email existe déjà."]);
        exit;
    }

    // Vérifier si le username existe déjà
    $username = htmlspecialchars(strip_tags(trim($data->username)));
    $stmtUsername = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmtUsername->bindParam(':username', $username, PDO::PARAM_STR);
    $stmtUsername->execute();

    if ($stmtUsername->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Ce nom d'utilisateur est déjà pris."]);
        exit;
    }

    // Hashage du mot de passe
    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

    // Insertion de l'utilisateur
    $query = "INSERT INTO users (last_name, first_name, username, email, password, role, is_active, must_change_password, created_at) 
              VALUES (:last_name, :first_name, :username, :email, :password, 'client', 1, 0, NOW())";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':last_name' => htmlspecialchars(strip_tags(trim($data->last_name))),
        ':first_name' => htmlspecialchars(strip_tags(trim($data->first_name))),
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    $userId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "message" => "Compte créé avec succès.",
        "user_id" => $userId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}
?>
