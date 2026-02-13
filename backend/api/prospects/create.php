<?php
// Headers CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Méthode non autorisée']);
    exit();
}

// Connexion à la base de données
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload JSON invalide']);
    exit();
}

// Validation des champs requis
$requiredFields = ['company_name', 'last_name', 'first_name', 'email', 'phone', 'location', 'event_type', 'planned_date', 'estimated_participants', 'needs_description'];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Champs requis manquants',
        'missing_fields' => $missingFields
    ]);
    exit();
}

// Validation de l'email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Format d\'email invalide']);
    exit();
}

// Validation du nombre de participants
if (!is_numeric($data['estimated_participants']) || (int)$data['estimated_participants'] < 1) {
    http_response_code(400);
    echo json_encode(['message' => 'Le nombre de participants doit être un nombre positif']);
    exit();
}

// Validation de la date (doit être aujourd'hui ou future)
$plannedDate = strtotime($data['planned_date']);
if ($plannedDate === false || $plannedDate < strtotime('today')) {
    http_response_code(400);
    echo json_encode(['message' => 'La date prévue doit être dans le futur']);
    exit();
}

// Nettoyer les données
$company_name = htmlspecialchars(strip_tags($data['company_name']));
$last_name = htmlspecialchars(strip_tags($data['last_name']));
$first_name = htmlspecialchars(strip_tags($data['first_name']));
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(strip_tags($data['phone']));
$location = htmlspecialchars(strip_tags($data['location']));
$event_type = htmlspecialchars(strip_tags($data['event_type']));
$planned_date = $data['planned_date'];
$estimated_participants = (int)$data['estimated_participants'];
$needs_description = htmlspecialchars(strip_tags($data['needs_description']));

try {
    // Insérer le prospect
    $query = "INSERT INTO prospects (
        company_name,
        last_name,
        first_name,
        email,
        phone,
        location,
        event_type,
        planned_date,
        estimated_participants,
        needs_description,
        status,
        created_at
    ) VALUES (
        :company_name,
        :last_name,
        :first_name,
        :email,
        :phone,
        :location,
        :event_type,
        :planned_date,
        :estimated_participants,
        :needs_description,
        'to_contact',
        NOW()
    )";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_name', $company_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':event_type', $event_type);
    $stmt->bindParam(':planned_date', $planned_date);
    $stmt->bindParam(':estimated_participants', $estimated_participants);
    $stmt->bindParam(':needs_description', $needs_description);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['message' => 'Erreur lors de l\'enregistrement de la demande']);
        exit();
    }

    $prospect_id = $db->lastInsertId();

    // Notification email à Innov'Events (non bloquante)
    $mailWarning = null;
    try {
        $mailConfig = require '../../config/mail.php';
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->Port = $mailConfig['port'];
        $mail->SMTPAuth = !empty($mailConfig['username']);

        if ($mail->SMTPAuth) {
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['encryption'];
        }

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress('contact@innovevents.com', "Innov'Events Contact");
        $mail->addReplyTo($email, "{$first_name} {$last_name}");

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "[Prospect] Nouvelle demande de devis - {$company_name}";
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <h2 style='color: #3498db;'>Nouvelle demande de devis</h2>
            <table style='margin: 20px 0; border-collapse: collapse; width: 100%;'>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa; width: 220px;'><strong>Entreprise</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$company_name}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Contact</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$first_name} {$last_name}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Email</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$email}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Téléphone</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$phone}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Lieu</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$location}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Type d\'événement</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$event_type}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Date souhaitée</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$planned_date}</td></tr>
                <tr><td style='padding: 10px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Participants estimés</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$estimated_participants}</td></tr>
            </table>
            <h3 style='color: #2c3e50;'>Besoin en quelques mots :</h3>
            <div style='padding: 15px; background: #f8f9fa; border-radius: 5px; white-space: pre-wrap;'>{$needs_description}</div>
            <p style='margin-top: 20px; font-size: 12px; color: #999;'>Statut par défaut : à contacter</p>
        </body>
        </html>";

        $mail->AltBody = "Nouvelle demande de devis\n\nEntreprise: {$company_name}\nContact: {$first_name} {$last_name}\nEmail: {$email}\nTéléphone: {$phone}\nLieu: {$location}\nType d'événement: {$event_type}\nDate souhaitée: {$planned_date}\nParticipants estimés: {$estimated_participants}\n\nBesoin:\n{$needs_description}\n\nStatut par défaut: à contacter";
        $mail->send();
    } catch (MailException $e) {
        $mailWarning = 'Demande enregistrée, mais email de notification non envoyé.';
    }

    http_response_code(201);
    echo json_encode([
        'message' => 'Merci pour votre demande. Chloé vous recontactera dans les plus brefs délais pour discuter de votre projet.',
        'prospect_id' => $prospect_id,
        'mail_warning' => $mailWarning
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}
?>
