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
$username = !empty($data['username']) ? htmlspecialchars(strip_tags($data['username'])) : null;
$title = htmlspecialchars(strip_tags($data['title']));
$email = !empty($data['email']) ? filter_var($data['email'], FILTER_SANITIZE_EMAIL) : null;
$description = htmlspecialchars(strip_tags($data['description']));

// Simulation d'envoi d'email (en production, utiliser PHPMailer ou mail())
$to = 'contact@innovevents.com';
$subject = "[Contact] " . $title;
$message = "Nom d'utilisateur: " . ($username ?? 'Non fourni') . "\n";
$message .= "Email: " . ($email ?? 'Non fourni') . "\n\n";
$message .= "Message:\n" . $description;

// En dev, on log simplement
error_log("=== NOUVEAU MESSAGE CONTACT ===");
error_log($message);
error_log("================================");

// En production: mail($to, $subject, $message);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
]);