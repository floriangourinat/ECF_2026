<?php
/**
 * API : création d'un devis
 *
 * Cet endpoint permet à un administrateur de créer un devis rattaché à un événement.
 * Le devis contient une ou plusieurs prestations. Le traitement est encadré par une
 * transaction SQL afin d'éviter de conserver un devis partiel si l'insertion d'une
 * prestation échoue.
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

// La création d'un devis est une action commerciale réservée à l'administrateur.
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

if (empty($data['event_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Événement requis'
    ]);
    exit();
}

if (empty($data['services']) || !is_array($data['services'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Au moins une prestation est requise'
    ]);
    exit();
}

foreach ($data['services'] as $service) {
    if (empty($service['label']) || !isset($service['unit_price_ht'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Chaque prestation doit contenir un libellé et un prix HT'
        ]);
        exit();
    }

    if (!is_numeric($service['unit_price_ht']) || (float) $service['unit_price_ht'] < 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Le prix HT d’une prestation doit être un nombre positif'
        ]);
        exit();
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // Les montants sont calculés côté serveur afin de ne pas faire confiance au front-end.
    $totalHT = 0.0;

    foreach ($data['services'] as $service) {
        $totalHT += (float) $service['unit_price_ht'];
    }

    $taxRate = isset($data['tax_rate']) && is_numeric($data['tax_rate'])
        ? (float) $data['tax_rate']
        : 20.00;

    if ($taxRate < 0) {
        $taxRate = 20.00;
    }

    $totalTTC = $totalHT * (1 + $taxRate / 100);

    /**
     * Le statut initial dépend du schéma SQL utilisé.
     * Si l'ENUM contient "draft", on l'utilise ; sinon on garde "pending".
     */
    $initialStatus = 'pending';

    $stmtStatusColumn = $db->query("SHOW COLUMNS FROM quotes LIKE 'status'");
    $statusColumn = $stmtStatusColumn ? $stmtStatusColumn->fetch(PDO::FETCH_ASSOC) : null;
    $statusType = strtolower((string) ($statusColumn['Type'] ?? ''));

    if (strpos($statusType, "'draft'") !== false) {
        $initialStatus = 'draft';
    }

    $stmtQuote = $db->prepare("
        INSERT INTO quotes (
            event_id,
            total_ht,
            tax_rate,
            total_ttc,
            issue_date,
            status,
            created_at
        ) VALUES (
            :event_id,
            :total_ht,
            :tax_rate,
            :total_ttc,
            :issue_date,
            :status,
            NOW()
        )
    ");

    $stmtQuote->execute([
        ':event_id' => (int) $data['event_id'],
        ':total_ht' => $totalHT,
        ':tax_rate' => $taxRate,
        ':total_ttc' => $totalTTC,
        ':issue_date' => date('Y-m-d'),
        ':status' => $initialStatus
    ]);

    $quoteId = (int) $db->lastInsertId();

    $stmtService = $db->prepare("
        INSERT INTO services (
            quote_id,
            label,
            description,
            unit_price_ht,
            created_at
        ) VALUES (
            :quote_id,
            :label,
            :description,
            :unit_price_ht,
            NOW()
        )
    ");

    foreach ($data['services'] as $service) {
        $label = htmlspecialchars(
            strip_tags(trim((string) $service['label'])),
            ENT_QUOTES,
            'UTF-8'
        );

        $description = !empty($service['description'])
            ? htmlspecialchars(strip_tags(trim((string) $service['description'])), ENT_QUOTES, 'UTF-8')
            : null;

        $stmtService->execute([
            ':quote_id' => $quoteId,
            ':label' => $label,
            ':description' => $description,
            ':unit_price_ht' => (float) $service['unit_price_ht']
        ]);
    }

    $db->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Devis créé avec succès',
        'data' => [
            'id' => $quoteId,
            'event_id' => (int) $data['event_id'],
            'total_ht' => $totalHT,
            'tax_rate' => $taxRate,
            'total_ttc' => $totalTTC,
            'status' => $initialStatus
        ]
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}