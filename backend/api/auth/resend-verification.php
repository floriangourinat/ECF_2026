<?php
/**
 * API Endpoint: Renvoyer email de vérification
 * POST /api/auth/resend-verification.php
 * Body JSON: { "email": "..." }
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

$email = filter_var(trim((string)$data->email), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT id, first_name, last_name, email_verified FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // Réponse générique anti-enumération
    if ($stmt->rowCount() === 0) {
        http_response_code(200);
        echo json_encode(["message" => "Si un compte existe, un email de vérification a été renvoyé."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Déjà vérifié -> réponse OK
    if ((int)$user['email_verified'] === 1) {
        http_response_code(200);
        echo json_encode(["message" => "Votre email est déjà vérifié. Vous pouvez vous connecter."]);
        exit;
    }

    // Nouveau token
    $newToken = bin2hex(random_bytes(32));
    $upd = $db->prepare("UPDATE users SET email_verification_token = :tkn, updated_at = NOW() WHERE id = :id");
    $upd->bindParam(':tkn', $newToken, PDO::PARAM_STR);
    $upd->bindParam(':id', $user['id'], PDO::PARAM_INT);
    $upd->execute();

    // Envoi email
    $mailConfig = require '../../config/mail.php';
    $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:4200';
    $verifyUrl = rtrim($frontendUrl, '/') . '/verify-email?token=' . urlencode($newToken);

    $firstName = $user['first_name'] ?? '';
    $lastName = $user['last_name'] ?? '';

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
    $mail->addAddress($email, trim($firstName . ' ' . $lastName));

    $mail->isHTML(true);
    $mail->Subject = "Innov'Events - Renvoi de confirmation email";
    $mail->Body = "
      <div style='font-family: Arial, sans-serif; max-width: 640px; margin: 0 auto;'>
        <h2 style='color: #fa8a27;'>Innov'Events</h2>
        <p>Bonjour,</p>
        <p>Voici votre lien de confirmation :</p>
        <p style='margin: 20px 0;'>
          <a href='{$verifyUrl}' style='display:inline-block; background:#334659; color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none;'>
            Confirmer mon email
          </a>
        </p>
        <p style='word-break: break-all; color:#334659;'>{$verifyUrl}</p>
      </div>
    ";
    $mail->AltBody = "Confirmez votre email : {$verifyUrl}";
    $mail->send();

    http_response_code(200);
    echo json_encode(["message" => "Si un compte existe, un email de vérification a été renvoyé."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur."]);
}
?>
