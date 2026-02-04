<?php
/**
 * API: Créer un devis
 * POST /api/quotes/create.php
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

$data = json_decode(file_get_contents('php://input'), true);

// Validation
if (empty($data['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Événement requis']);
    exit();
}

if (empty($data['services']) || !is_array($data['services'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Au moins une prestation est requise']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // Calculer les totaux
    $totalHT = 0;
    foreach ($data['services'] as $service) {
        $totalHT += floatval($service['unit_price_ht']);
    }
    
    $taxRate = !empty($data['tax_rate']) ? floatval($data['tax_rate']) : 20.00;
    $totalTTC = $totalHT * (1 + $taxRate / 100);

    // Créer le devis
    $stmtQuote = $db->prepare("
        INSERT INTO quotes (event_id, total_ht, tax_rate, total_ttc, issue_date, status, created_at)
        VALUES (:event_id, :total_ht, :tax_rate, :total_ttc, :issue_date, 'pending', NOW())
    ");
    $stmtQuote->execute([
        ':event_id' => $data['event_id'],
        ':total_ht' => $totalHT,
        ':tax_rate' => $taxRate,
        ':total_ttc' => $totalTTC,
        ':issue_date' => date('Y-m-d')
    ]);
    $quoteId = $db->lastInsertId();

    // Créer les prestations
    $stmtService = $db->prepare("
        INSERT INTO services (quote_id, label, description, unit_price_ht, created_at)
        VALUES (:quote_id, :label, :description, :unit_price_ht, NOW())
    ");

    foreach ($data['services'] as $service) {
        $stmtService->execute([
            ':quote_id' => $quoteId,
            ':label' => htmlspecialchars(strip_tags($service['label'])),
            ':description' => !empty($service['description']) ? htmlspecialchars(strip_tags($service['description'])) : null,
            ':unit_price_ht' => floatval($service['unit_price_ht'])
        ]);
    }

    $db->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Devis créé avec succès',
        'data' => [
            'id' => $quoteId,
            'total_ht' => $totalHT,
            'total_ttc' => $totalTTC
        ]
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}