<?php
/**
 * API: Mettre à jour le statut d'un prospect
 * PUT /api/prospects/update_status.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit();
}

$validStatuses = ['to_contact', 'qualification', 'failed', 'converted'];
if (!in_array($data['status'], $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

if ($data['status'] === 'failed' && empty(trim((string)($data['failure_message'] ?? '')))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Un message d\'échec est requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtProspect = $db->prepare('SELECT id, first_name, last_name, email FROM prospects WHERE id = :id LIMIT 1');
    $stmtProspect->execute([':id' => $data['id']]);
    $prospect = $stmtProspect->fetch(PDO::FETCH_ASSOC);

    if (!$prospect) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Prospect non trouvé']);
        exit();
    }

    $stmt = $db->prepare('UPDATE prospects SET status = :status WHERE id = :id');
    $stmt->execute([
        ':status' => $data['status'],
        ':id' => $data['id']
    ]);

    if ($data['status'] === 'failed') {
        $mailConfig = require '../../config/mail.php';
        $failureMessage = htmlspecialchars(trim((string)$data['failure_message']));

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->Port = $mailConfig['port'];
        $mail->SMTPAuth = !empty($mailConfig['username']);

        if ($mail->SMTPAuth) {
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            if (!empty($mailConfig['encryption'])) {
                $mail->SMTPSecure = $mailConfig['encryption'];
            }
        }

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($prospect['email'], trim(($prospect['first_name'] ?? '') . ' ' . ($prospect['last_name'] ?? '')));
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = 'Mise à jour de votre demande de devis';
        $mail->Body = "
            <p>Bonjour {$prospect['first_name']},</p>
            <p>Suite à la qualification de votre besoin, votre demande de devis ne peut pas être poursuivie.</p>
            <p><strong>Message :</strong><br>{$failureMessage}</p>
            <p>Nous restons à votre disposition pour étudier une autre demande.</p>
            <p>Cordialement,<br>L'équipe Innov'Events</p>
        ";
        $mail->AltBody = "Bonjour {$prospect['first_name']},\n\nSuite à la qualification de votre besoin, votre demande de devis ne peut pas être poursuivie.\n\nMessage : " . trim((string)$data['failure_message']) . "\n\nCordialement,\nL'équipe Innov'Events";

        try {
            $mail->send();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Statut mis à jour, mais email non envoyé']);
            exit();
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
