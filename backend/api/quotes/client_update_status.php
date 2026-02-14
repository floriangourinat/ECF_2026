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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../config/mail.php';
require_once '../../middleware/auth.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$payload = require_auth(['client']);
$authUserId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['user_id']) || empty($data['quote_id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
    exit();
}

$targetUserId = (int)$data['user_id'];
if ($targetUserId !== $authUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
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

    $stmt = $db->prepare(
        'SELECT q.id, q.status, e.name as event_name, e.start_date, e.location,
                u.first_name, u.last_name, u.email, c.company_name
         FROM quotes q
         JOIN events e ON q.event_id = e.id
         JOIN clients c ON e.client_id = c.id
         JOIN users u ON c.user_id = u.id
         WHERE q.id = :quote_id AND c.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        ':quote_id' => (int)$data['quote_id'],
        ':user_id' => $targetUserId
    ]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    $sql = 'UPDATE quotes SET status = :status, updated_at = NOW()';
    $params = [
        ':status' => $data['status'],
        ':id' => (int)$data['quote_id']
    ];

    if ($data['status'] === 'modification' && !empty($data['modification_reason'])) {
        $sql .= ', modification_reason = :reason';
        $params[':reason'] = htmlspecialchars(strip_tags((string)$data['modification_reason']));
    }

    $sql .= ' WHERE id = :id';
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute($params);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('MODIFICATION_STATUT_DEVIS_CLIENT', 'quote', (int)$data['quote_id'], $authUserId, [
        'ancien_statut' => $quote['status'],
        'nouveau_statut' => $data['status']
    ]);

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
            $mail->CharSet = 'UTF-8';

            $statusLabel = $data['status'] === 'accepted' ? 'accepté' : 'demande de modification';
            $mail->Subject = 'Mise à jour devis client - ' . $statusLabel;

            $body = "Le client {$quote['first_name']} {$quote['last_name']}";
            if (!empty($quote['company_name'])) {
                $body .= " ({$quote['company_name']})";
            }
            $body .= " a mis à jour le devis #{$quote['id']}\n\n";
            $body .= "Nouveau statut : {$data['status']}\n";
            $body .= "Événement : {$quote['event_name']}\n";
            $body .= "Date : {$quote['start_date']}\n";
            $body .= "Lieu : {$quote['location']}\n";

            if ($data['status'] === 'modification' && !empty($data['modification_reason'])) {
                $body .= "\nMotif de modification:\n{$data['modification_reason']}\n";
            }

            $mail->Body = $body;
            $mail->send();
        } catch (Exception $mailException) {
            // Ne pas faire échouer la requête métier si l'email échoue
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour',
        'data' => ['status' => $data['status']]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
