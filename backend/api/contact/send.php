<?php
/**
 * API: Envoi formulaire de contact
 * POST /api/contact/send.php
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

require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

// Validation
$errors = [];

if (empty($data['title'])) {
    $errors[] = 'Le titre est requis';
}

if (empty($data['description'])) {
    $errors[] = 'La description est requise';
}

// Email obligatoire si pas de nom d'utilisateur
if (empty($data['username']) && empty($data['email'])) {
    $errors[] = 'L\'email est requis si le nom d\'utilisateur n\'est pas fourni';
}

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format d\'email invalide';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Sanitization
$username = !empty($data['username']) ? htmlspecialchars(strip_tags($data['username'])) : 'Non fourni';
$title = htmlspecialchars(strip_tags($data['title']));
$email = !empty($data['email']) ? filter_var($data['email'], FILTER_SANITIZE_EMAIL) : 'Non fourni';
$description = htmlspecialchars(strip_tags($data['description']));

try {
    // Configuration email
    $mailConfig = require '../../config/mail.php';

    $mail = new PHPMailer(true);

    // Configuration SMTP (Mailhog en dev)
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->Port = $mailConfig['port'];
    $mail->SMTPAuth = !empty($mailConfig['username']);
    
    if ($mail->SMTPAuth) {
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
    }

    // Expéditeur
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    
    // Destinataire (email générique de la société)
    $mail->addAddress('contact@innovevents.com', "Innov'Events Contact");

    // Répondre à l'expéditeur si email fourni
    if ($email !== 'Non fourni') {
        $mail->addReplyTo($email, $username);
    }

    // Contenu
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = "[Contact] " . $title;
    
    $mail->Body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <h2 style='color: #3498db;'>Nouveau message de contact</h2>
        
        <table style='margin: 20px 0; border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa; width: 150px;'><strong>Nom d'utilisateur</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$username}</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Email</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$email}</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Sujet</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$title}</td>
            </tr>
        </table>
        
        <h3 style='color: #2c3e50;'>Message :</h3>
        <div style='padding: 15px; background: #f8f9fa; border-radius: 5px; white-space: pre-wrap;'>{$description}</div>
        
        <hr style='margin-top: 30px; border: none; border-top: 1px solid #eee;'>
        <p style='font-size: 12px; color: #999;'>
            Ce message a été envoyé depuis le formulaire de contact du site Innov'Events.
        </p>
    </body>
    </html>";

    $mail->AltBody = "Nouveau message de contact\n\nNom d'utilisateur: {$username}\nEmail: {$email}\nSujet: {$title}\n\nMessage:\n{$description}";

    // Envoyer
    $mail->send();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()]);
}