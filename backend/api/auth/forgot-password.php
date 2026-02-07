<?php
/**
 * API Endpoint: Mot de passe oublié
 * backend/api/auth/forgot-password.php
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

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email)) {
    http_response_code(400);
    echo json_encode(["message" => "L'email est requis."]);
    exit;
}

$email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, first_name, email FROM users WHERE email = :email AND is_active = 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    // Message identique pour sécurité (éviter énumération des comptes)
    $response_msg = "Si cet email existe dans notre système, un nouveau mot de passe vous sera envoyé.";

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Générer mot de passe temporaire sécurisé
        $temp_password = generateSecurePassword(12);
        $hashed = password_hash($temp_password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Mettre à jour avec obligation de changer le mot de passe
        $update = $db->prepare("UPDATE users SET password = :pwd, must_change_password = 1 WHERE id = :id");
        $update->bindParam(':pwd', $hashed);
        $update->bindParam(':id', $user['id']);
        $update->execute();

        // TODO: Envoyer email avec PHPMailer
        // sendPasswordResetEmail($user['email'], $user['first_name'], $temp_password);
        
        // En développement, on retourne le mot de passe pour tester
        // À SUPPRIMER EN PRODUCTION !
        http_response_code(200);
        echo json_encode([
            "message" => $response_msg,
            "debug_temp_password" => $temp_password // SUPPRIMER EN PROD
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode(["message" => $response_msg]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur.", "error" => $e->getMessage()]);
}

/**
 * Génère un mot de passe sécurisé respectant les critères
 */
function generateSecurePassword($length = 12) {
    $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lowercase = 'abcdefghjkmnpqrstuvwxyz';
    $numbers = '23456789';
    $special = '@$!%*?&';
    
    $password = '';
    // Garantir au moins un caractère de chaque type
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Compléter avec des caractères aléatoires
    $all = $uppercase . $lowercase . $numbers . $special;
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Mélanger le mot de passe
    return str_shuffle($password);
}
?>