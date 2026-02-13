<?php
/**
 * API Endpoint: Inscription utilisateur (Client)
 * POST /api/auth/register.php
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

// Nettoyage
$emailRaw = trim((string)$data->email);
$passwordRaw = (string)$data->password;
$lastNameRaw = trim((string)$data->last_name);
$firstNameRaw = trim((string)$data->first_name);
$usernameRaw = trim((string)$data->username);

// Validation email
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(["message" => "Format d'email invalide."]);
    exit;
}

// Validation mot de passe : 8+ / 1 minuscule / 1 majuscule / 1 chiffre / 1 spécial
$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
if (!preg_match($passwordPattern, $passwordRaw)) {
    http_response_code(400);
    echo json_encode([
        "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial."
    ]);
    exit;
}

// Validation / nettoyage champs texte
$lastName = htmlspecialchars(strip_tags($lastNameRaw));
$firstName = htmlspecialchars(strip_tags($firstNameRaw));
$username = htmlspecialchars(strip_tags($usernameRaw));

if (strlen($lastName) < 1 || strlen($lastName) > 100) {
    http_response_code(400);
    echo json_encode(["message" => "Nom invalide (1 à 100 caractères)."]);
    exit;
}
if (strlen($firstName) < 1 || strlen($firstName) > 100) {
    http_response_code(400);
    echo json_encode(["message" => "Prénom invalide (1 à 100 caractères)."]);
    exit;
}
if (strlen($username) < 3 || strlen($username) > 100) {
    http_response_code(400);
    echo json_encode(["message" => "Nom d'utilisateur invalide (3 à 100 caractères)."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si email existe déjà
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmtCheck->execute([':email' => $email]);
    if ($stmtCheck->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Un compte avec cet email existe déjà."]);
        exit;
    }

    // Vérifier si username existe déjà
    $stmtUsername = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmtUsername->execute([':username' => $username]);
    if ($stmtUsername->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Ce nom d'utilisateur est déjà pris."]);
        exit;
    }

    // Transaction : user + client profil
    $db->beginTransaction();

    $hashedPassword = password_hash($passwordRaw, PASSWORD_BCRYPT);
    $emailVerificationToken = bin2hex(random_bytes(32));

    // Insert user
    $queryUser = "INSERT INTO users (
                    last_name, first_name, username, email, password,
                    role, is_active, email_verified, email_verification_token,
                    must_change_password, created_at
                  ) VALUES (
                    :last_name, :first_name, :username, :email, :password,
                    'client', 1, 0, :token,
                    0, NOW()
                  )";

    $stmtUser = $db->prepare($queryUser);
    $stmtUser->execute([
        ':last_name' => $lastName,
        ':first_name' => $firstName,
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':token' => $emailVerificationToken
    ]);

    $userId = (int)$db->lastInsertId();

    $queryClient = "INSERT INTO clients (user_id, company_name, phone, address, created_at)
                    VALUES (:user_id, NULL, NULL, NULL, NOW())";
    $stmtClient = $db->prepare($queryClient);
    $stmtClient->execute([':user_id' => $userId]);

    $db->commit();

    // Envoi email confirmation (avec lien)
    $mailSent = true;
    try {
        $mailConfig = require '../../config/mail.php';
        $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:4200';
        $verifyUrl = rtrim($frontendUrl, '/') . '/verify-email?token=' . urlencode($emailVerificationToken);

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
        $mail->addAddress($email, $firstName . ' ' . $lastName);

        $mail->isHTML(true);
        $mail->Subject = "Innov'Events - Confirmez votre adresse email";
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; max-width: 640px; margin: 0 auto;'>
            <h2 style='color: #fa8a27;'>Innov'Events</h2>
            <p>Bonjour <strong>{$firstName}</strong>,</p>
            <p>Votre compte a bien été créé. Pour finaliser l'inscription, merci de confirmer votre adresse email :</p>
            <p style='margin: 20px 0;'>
              <a href='{$verifyUrl}' style='display:inline-block; background:#334659; color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none;'>
                Confirmer mon email
              </a>
            </p>
            <p>Si le bouton ne fonctionne pas, copiez/collez ce lien :</p>
            <p style='word-break: break-all; color:#334659;'>{$verifyUrl}</p>
            <hr style='border:none; border-top:1px solid #eee; margin: 20px 0;'>
            <p style='color:#999; font-size:12px;'>Cet email a été envoyé automatiquement par Innov'Events.</p>
          </div>
        ";
        $mail->AltBody = "Confirmez votre email en ouvrant ce lien : {$verifyUrl}";
        $mail->send();

    } catch (Exception $e) {
        // Le compte est créé, mais l'email n'a pas pu partir.
        // On laisse la possibilité de renvoyer via /resend-verification.php
        $mailSent = false;
    }

    http_response_code(201);
    echo json_encode([
        "message" => $mailSent
            ? "Compte créé avec succès. Un email de confirmation vous a été envoyé."
            : "Compte créé avec succès. L'email n'a pas pu être envoyé, veuillez utiliser le renvoi de confirmation.",
        "user_id" => $userId
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(["message" => "Email ou nom d'utilisateur déjà utilisé."]);
        exit;
    }

    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur."]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur."]);
}
?>
