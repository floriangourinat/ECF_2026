<?php
/**
 * API: Contreproposition admin sur un devis en modification
 * POST /api/quotes/admin_counter_proposal.php
 * Body JSON: { quote_id, admin_user_id, counter_proposal }
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
require_once '../../config/mail.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['quote_id']) || empty($data['admin_user_id']) || empty($data['counter_proposal'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'quote_id, admin_user_id et counter_proposal requis']);
    exit();
}

$counterProposal = trim((string)$data['counter_proposal']);
if ($counterProposal === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contreproposition ne peut pas être vide']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT q.id, q.status, q.modification_reason, e.name AS event_name,
               u.email AS client_email, u.first_name, u.last_name
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        JOIN clients c ON e.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE q.id = :quote_id
        LIMIT 1
    ");
    $stmt->execute([':quote_id' => (int)$data['quote_id']]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    if ($quote['status'] !== 'modification') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le devis doit être au statut modification']);
        exit();
    }

    $marker = "[CONTREPROPOSITION_INNOV_EVENTS]";
    $baseReason = (string)($quote['modification_reason'] ?? '');
    $baseReason = trim(explode($marker, $baseReason)[0]);

    $dateLabel = date('d/m/Y H:i');
    $safeCounterProposal = htmlspecialchars(strip_tags($counterProposal));
    $combinedReason = trim($baseReason);
    if ($combinedReason !== '') {
        $combinedReason .= "\n\n";
    }
    $combinedReason .= $marker . "\nDate: {$dateLabel}\nMessage: {$safeCounterProposal}";

    $stmtUpdate = $db->prepare("UPDATE quotes SET status = 'pending', modification_reason = :reason, updated_at = NOW() WHERE id = :id");
    $stmtUpdate->execute([
        ':reason' => $combinedReason,
        ':id' => (int)$data['quote_id']
    ]);

    $mailConfig = include '../../config/mail.php';
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = !empty($mailConfig['username']);
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        if (!empty($mailConfig['encryption'])) {
            $mail->SMTPSecure = $mailConfig['encryption'];
        }
        $mail->Port = $mailConfig['port'];

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($quote['client_email'], trim(($quote['first_name'] ?? '') . ' ' . ($quote['last_name'] ?? '')));

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = 'Contreproposition pour votre devis - ' . $quote['event_name'];
        $mail->Body = "
            <h2>Contreproposition Innov'events</h2>
            <p>Nous avons étudié votre demande de modification.</p>
            <p><strong>Événement :</strong> {$quote['event_name']}</p>
            <p><strong>Notre contreproposition :</strong></p>
            <p>" . nl2br(htmlspecialchars($counterProposal)) . "</p>
            <p>Vous pouvez revenir sur votre espace client pour accepter ou refuser ce devis.</p>
        ";
        $mail->AltBody = "Contreproposition Innov'events\nÉvénement: {$quote['event_name']}\n\n{$counterProposal}\n";
        $mail->send();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur envoi email: ' . $mail->ErrorInfo]);
        exit();
    }

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('ADMIN_COUNTER_PROPOSAL', 'quote', (int)$data['quote_id'], (int)$data['admin_user_id'], [
        'counter_proposal' => $counterProposal,
        'status_after' => 'pending'
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Contreproposition envoyée au client',
        'data' => [
            'status' => 'pending',
            'modification_reason' => $combinedReason
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
