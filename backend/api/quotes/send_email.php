<?php
/**
 * API : envoi d'un devis par e-mail
 *
 * Cet endpoint permet à un administrateur d'envoyer un devis au client.
 * Il récupère les informations du devis, génère le PDF, configure PHPMailer,
 * ajoute le document en pièce jointe, envoie l'e-mail puis met à jour le statut.
 */

require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// L'envoi d'un devis est une action commerciale sensible réservée à l'administrateur.
$currentUser = require_auth(['admin']);

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format JSON invalide'
    ]);
    exit();
}

if (empty($data['quote_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID du devis requis'
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupération du devis, de l'événement et des informations du client destinataire.
    $stmt = $db->prepare("
        SELECT q.*, e.name AS event_name,
               u.email AS client_email, u.first_name, u.last_name,
               c.company_name
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        JOIN clients c ON e.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE q.id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => (int) $data['quote_id']
    ]);

    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Devis non trouvé'
        ]);
        exit();
    }

    if (empty($quote['client_email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email client non disponible'
        ]);
        exit();
    }

    // Génération du PDF via l'endpoint interne existant.
    $pdfUrl = 'http://localhost/api/quotes/generate_pdf.php?id=' . (int) $data['quote_id'] . '&output=string';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pdfUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $pdfContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($httpCode !== 200 || empty($pdfContent)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur génération PDF',
            'details' => $curlError ?: null
        ]);
        exit();
    }

    // Configuration e-mail.
    $mailConfig = require __DIR__ . '/../../config/mail.php';

    $mail = new PHPMailer(true);

    // Configuration SMTP.
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->Port = $mailConfig['port'];
    $mail->SMTPAuth = !empty($mailConfig['username']);

    if ($mail->SMTPAuth) {
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
    }

    // Expéditeur et destinataire.
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress(
        $quote['client_email'],
        trim($quote['first_name'] . ' ' . $quote['last_name'])
    );

    // Contenu de l'e-mail.
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = "Votre devis Innov'Events - " . $quote['event_name'];

    $mail->Body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <h2 style='color: #3498db;'>Bonjour {$quote['first_name']},</h2>

        <p>Veuillez trouver ci-joint le devis pour votre événement <strong>{$quote['event_name']}</strong>.</p>

        <table style='margin: 20px 0; border-collapse: collapse;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Référence</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>DEV-" . str_pad($quote['id'], 5, '0', STR_PAD_LEFT) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Montant TTC</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . number_format((float) $quote['total_ttc'], 2, ',', ' ') . " €</td>
            </tr>
        </table>

        <p>N'hésitez pas à nous contacter pour toute question.</p>

        <p>Cordialement,<br>
        <strong>L'équipe Innov'Events</strong></p>

        <hr style='margin-top: 30px; border: none; border-top: 1px solid #eee;'>
        <p style='font-size: 12px; color: #999;'>
            Innov'Events - Organisation d'événements professionnels<br>
            contact@innovevents.com
        </p>
    </body>
    </html>";

    $mail->AltBody = "Bonjour {$quote['first_name']},\n\n"
        . "Veuillez trouver ci-joint le devis pour votre événement {$quote['event_name']}.\n\n"
        . "Montant TTC : " . number_format((float) $quote['total_ttc'], 2, ',', ' ') . " €\n\n"
        . "Cordialement,\nL'équipe Innov'Events";

    // Pièce jointe PDF.
    $mail->addStringAttachment(
        $pdfContent,
        'Devis_' . str_pad($quote['id'], 5, '0', STR_PAD_LEFT) . '.pdf',
        'base64',
        'application/pdf'
    );

    // Envoi de l'e-mail.
    $mail->send();

    // Au moment de l'envoi au client, le devis passe en étude côté client.
    $stmtUpdateStatus = $db->prepare("
        UPDATE quotes
        SET status = 'pending', updated_at = NOW()
        WHERE id = :id
    ");

    $stmtUpdateStatus->execute([
        ':id' => (int) $data['quote_id']
    ]);

    // Journalisation de l'action.
    try {
        require_once __DIR__ . '/../../services/MongoLogger.php';

        $logger = new MongoLogger();
        $logger->log('quote_email_sent', 'quote', (int) $quote['id'], (int) $currentUser['user_id'], [
            'recipient' => $quote['client_email'],
            'event' => $quote['event_name']
        ]);
    } catch (Exception $e) {
        // Une erreur de journalisation ne doit pas bloquer l'envoi du devis.
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Devis envoyé à ' . $quote['client_email'],
        'data' => [
            'status' => 'pending'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur envoi email: ' . $e->getMessage()
    ]);
}