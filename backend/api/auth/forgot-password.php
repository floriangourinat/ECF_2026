<?php
/**
 * API Endpoint: Mot de passe oublié
 * POST /api/auth/forgot-password.php
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
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email)) {
    http_response_code(400);
    echo json_encode(["message" => "Email requis."]);
    exit;
}

$email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // Toujours répondre succès pour ne pas révéler si l'email existe
    if ($stmt->rowCount() === 0) {
        http_response_code(200);
        echo json_encode(["message" => "Si un compte existe avec cet email, un nouveau mot de passe vous sera envoyé."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Générer un nouveau mot de passe temporaire
    $tempPassword = bin2hex(random_bytes(6)); // 12 caractères
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

    // Mettre à jour le mot de passe + forcer le changement à la prochaine connexion
    $stmtUpdate = $db->prepare("UPDATE users SET password = :password, must_change_password = 1 WHERE id = :id");
    $stmtUpdate->execute([
        ':password' => $hashedPassword,
        ':id' => $user['id']
    ]);

    // Envoyer l'email avec le nouveau mot de passe
    $mailConfig = require '../../config/mail.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->Port = $mailConfig['port'];
    $mail->SMTPAuth = !empty($mailConfig['username']);
    if ($mail->SMTPAuth) {
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
    }
    if (!empty($mailConfig['encryption'])) {
        $mail->SMTPSecure = $mailConfig['encryption'];
    }
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

    $mail->isHTML(true);
    $mail->Subject = "Innov'Events - Réinitialisation de votre mot de passe";
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #667eea;'>Innov'Events</h2>
            <p>Bonjour {$user['first_name']},</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Voici votre nouveau mot de passe temporaire :</p>
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                <strong style='font-size: 18px; letter-spacing: 2px;'>{$tempPassword}</strong>
            </div>
            <p><strong>Important :</strong> Vous devrez modifier ce mot de passe lors de votre prochaine connexion.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, veuillez contacter notre support.</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='color: #999; font-size: 12px;'>Cet email a été envoyé automatiquement par Innov'Events.</p>
        </div>
    ";
    $mail->AltBody = "Bonjour {$user['first_name']}, votre nouveau mot de passe temporaire est : {$tempPassword}. Vous devrez le modifier lors de votre prochaine connexion.";

    $mail->send();

    http_response_code(200);
    echo json_encode([
        "message" => "Si un compte existe avec cet email, un nouveau mot de passe vous sera envoyé."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur lors de l'envoi de l'email.", "error" => $e->getMessage()]);
}
?>
