<?php
/**
 * API: Mise à jour statut devis par client
 * PUT /api/quotes/client_update_status.php
 * Body JSON: { user_id, quote_id, status, modification_reason? }
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../config/mail.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['user_id']) || empty($data['quote_id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
    exit();
}

$allowedStatuses = ['accepted', 'modification', 'refused'];
if (!in_array($data['status'], $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut non autorisé']);
    exit();
}

if ($data['status'] === 'modification' && empty($data['modification_reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Motif de modification requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT q.id, q.status, e.name as event_name, e.start_date, e.location,
               u.first_name, u.last_name, u.email, c.company_name
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        JOIN clients c ON e.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE q.id = :quote_id AND c.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':quote_id' => $data['quote_id'],
        ':user_id' => $data['user_id']
    ]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    $sql = "UPDATE quotes SET status = :status, updated_at = NOW()";
    $params = [
        ':status' => $data['status'],
        ':id' => $data['quote_id']
    ];

    if ($data['status'] === 'modification' && !empty($data['modification_reason'])) {
        $sql .= ", modification_reason = :reason";
        $params[':reason'] = htmlspecialchars(strip_tags($data['modification_reason']));
    }

    $sql .= " WHERE id = :id";
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute($params);

    $mailConfig = include '../../config/mail.php';
    $notifyStatuses = ['accepted', 'modification'];

    if (in_array($data['status'], $notifyStatuses, true)) {
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
            $mail->addAddress($mailConfig['from_email'], $mailConfig['from_name']);

            $clientName = $quote['company_name'] ?: ($quote['first_name'] . ' ' . $quote['last_name']);
            $statusLabel = $data['status'] === 'accepted' ? 'accepté' : 'demande de modification';
            $mail->Subject = "Devis {$statusLabel} - {$quote['event_name']}";

            $reasonHtml = '';
            if ($data['status'] === 'modification') {
                $reasonHtml = "<p><strong>Motif :</strong> " . htmlspecialchars($data['modification_reason']) . "</p>";
            }

            $mail->isHTML(true);
            $mail->Body = "
                <h2>Réponse client au devis</h2>
                <p><strong>Client :</strong> {$clientName}</p>
                <p><strong>Email :</strong> {$quote['email']}</p>
                <p><strong>Événement :</strong> {$quote['event_name']}</p>
                <p><strong>Statut :</strong> {$statusLabel}</p>
                {$reasonHtml}
            ";
            $mail->AltBody = "Devis {$statusLabel} - {$quote['event_name']}\nClient: {$clientName}\nEmail: {$quote['email']}\n" . ($data['status'] === 'modification' ? "Motif: {$data['modification_reason']}\n" : '');

            $mail->send();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur envoi email: ' . $mail->ErrorInfo]);
            exit();
        }
    }

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('CLIENT_UPDATE_QUOTE', 'quote', (int)$data['quote_id'], (int)$data['user_id'], [
        'status' => $data['status'],
        'reason' => $data['modification_reason'] ?? null
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
