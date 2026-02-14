<?php
/**
 * API: Envoyer un devis par email
 * POST /api/quotes/send_email.php
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

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['quote_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du devis requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer le devis avec les infos client
    $stmt = $db->prepare("
        SELECT q.*, e.name as event_name, 
               u.email as client_email, u.first_name, u.last_name,
               c.company_name
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        JOIN clients c ON e.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE q.id = :id
    ");
    $stmt->execute([':id' => $data['quote_id']]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    if (empty($quote['client_email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email client non disponible']);
        exit();
    }

    // Générer le PDF via curl interne
    $pdfUrl = 'http://localhost/api/quotes/generate_pdf.php?id=' . $data['quote_id'] . '&output=string';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pdfUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $pdfContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($pdfContent)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur génération PDF']);
        exit();
    }

    // Configuration email
    $mailConfig = require '../../config/mail.php';

    $mail = new PHPMailer(true);

    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->Port = $mailConfig['port'];
    $mail->SMTPAuth = !empty($mailConfig['username']);
    
    if ($mail->SMTPAuth) {
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
    }

    // Expéditeur et destinataire
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress($quote['client_email'], $quote['first_name'] . ' ' . $quote['last_name']);

    // Contenu
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
                <td style='padding: 8px; border: 1px solid #ddd;'>" . number_format($quote['total_ttc'], 2, ',', ' ') . " €</td>
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

    $mail->AltBody = "Bonjour {$quote['first_name']},\n\nVeuillez trouver ci-joint le devis pour votre événement {$quote['event_name']}.\n\nMontant TTC: " . number_format($quote['total_ttc'], 2, ',', ' ') . " €\n\nCordialement,\nL'équipe Innov'Events";

    // Pièce jointe PDF
    $mail->addStringAttachment($pdfContent, 'Devis_' . str_pad($quote['id'], 5, '0', STR_PAD_LEFT) . '.pdf', 'base64', 'application/pdf');

    // Envoyer
    $mail->send();

    // Au moment de l'envoi au client, le devis passe en étude côté client
    $stmtUpdateStatus = $db->prepare("UPDATE quotes SET status = 'pending', updated_at = NOW() WHERE id = :id");
    $stmtUpdateStatus->execute([':id' => $data['quote_id']]);

    // Logger l'action
    try {
        require_once '../../services/MongoLogger.php';
        $logger = new MongoLogger();
        $logger->log('quote_email_sent', 'quote', (int)$quote['id'], null, [
            'recipient' => $quote['client_email'],
            'event' => $quote['event_name']
        ]);
    } catch (Exception $e) {
        // Ignorer les erreurs de log
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
    echo json_encode(['success' => false, 'message' => 'Erreur envoi email: ' . $e->getMessage()]);
}
