<?php
/**
 * API: Générer PDF d'un devis
 * GET /api/quotes/generate_pdf.php?id=1
 */

require_once '../../vendor/autoload.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$quoteId = isset($_GET['id']) ? $_GET['id'] : null;
$outputMode = isset($_GET['output']) ? $_GET['output'] : 'download';

if (empty($quoteId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID devis requis']);
    exit();
}

// Authentification : récupérer l'utilisateur connecté si possible
// En appel interne (ex: send_email.php via curl), il n'y a pas de token
$authUserId = null;
$token = getBearerToken();
if ($token) {
    $payload = require_auth(['admin']);
    $authUserId = (int)$payload['user_id'];
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer le devis
    $stmt = $db->prepare("
        SELECT q.*, e.name as event_name, e.start_date as event_date, e.location as event_location,
               c.company_name, c.phone as client_phone, c.address as client_address,
               u.first_name, u.last_name, u.email as client_email
        FROM quotes q
        JOIN events e ON q.event_id = e.id
        LEFT JOIN clients c ON e.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE q.id = :id
    ");
    $stmt->execute([':id' => $quoteId]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
        exit();
    }

    // Récupérer les prestations
    $stmtServices = $db->prepare("SELECT * FROM services WHERE quote_id = :quote_id");
    $stmtServices->execute([':quote_id' => $quoteId]);
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Infos document
    $pdf->SetCreator('Innov\'Events');
    $pdf->SetAuthor('Innov\'Events');
    $pdf->SetTitle('Devis #' . $quote['id']);

    // Supprimer header/footer par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Marges
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Logo et en-tête
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->SetTextColor(102, 126, 234);
    $pdf->Cell(0, 15, "Innov'Events", 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, '123 Avenue des Champs-Élysées', 0, 1, 'L');
    $pdf->Cell(0, 5, '75008 Paris, France', 0, 1, 'L');
    $pdf->Cell(0, 5, 'contact@innovevents.com | 01 23 45 67 89', 0, 1, 'L');

    $pdf->Ln(10);

    // Titre DEVIS
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->Cell(0, 10, 'DEVIS N° ' . str_pad($quote['id'], 5, '0', STR_PAD_LEFT), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, 'Date : ' . date('d/m/Y', strtotime($quote['issue_date'])), 0, 1, 'C');

    $pdf->Ln(10);

    // Infos client
    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->Cell(90, 8, 'CLIENT', 0, 0, 'L', true);
    $pdf->Cell(90, 8, 'ÉVÉNEMENT', 0, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(60, 60, 60);
    
    $clientName = $quote['company_name'] ?: $quote['first_name'] . ' ' . $quote['last_name'];
    $pdf->Cell(90, 6, $clientName, 0, 0, 'L');
    $pdf->Cell(90, 6, $quote['event_name'], 0, 1, 'L');
    
    $pdf->Cell(90, 6, $quote['client_email'], 0, 0, 'L');
    $pdf->Cell(90, 6, 'Date : ' . date('d/m/Y', strtotime($quote['event_date'])), 0, 1, 'L');
    
    $pdf->Cell(90, 6, $quote['client_phone'] ?: '', 0, 0, 'L');
    $pdf->Cell(90, 6, 'Lieu : ' . ($quote['event_location'] ?: '-'), 0, 1, 'L');

    $pdf->Ln(10);

    // Tableau des prestations
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(100, 8, 'PRESTATION', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'DESCRIPTION', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'MONTANT HT', 1, 1, 'R', true);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetFillColor(255, 255, 255);

    foreach ($services as $index => $service) {
        $fill = $index % 2 === 0;
        if ($fill) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $pdf->Cell(100, 7, $service['label'], 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, $service['description'] ?: '-', 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, number_format($service['unit_price_ht'], 2, ',', ' ') . ' €', 1, 1, 'R', $fill);
    }

    $pdf->Ln(5);

    // Totaux
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(140, 7, '', 0, 0);
    $pdf->Cell(25, 7, 'Total HT :', 0, 0, 'R');
    $pdf->Cell(15, 7, number_format($quote['total_ht'], 2, ',', ' ') . ' €', 0, 1, 'R');

    $pdf->Cell(140, 7, '', 0, 0);
    $pdf->Cell(25, 7, 'TVA (' . $quote['tax_rate'] . '%) :', 0, 0, 'R');
    $tva = $quote['total_ttc'] - $quote['total_ht'];
    $pdf->Cell(15, 7, number_format($tva, 2, ',', ' ') . ' €', 0, 1, 'R');

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(102, 126, 234);
    $pdf->Cell(140, 10, '', 0, 0);
    $pdf->Cell(25, 10, 'Total TTC :', 0, 0, 'R');
    $pdf->Cell(15, 10, number_format($quote['total_ttc'], 2, ',', ' ') . ' €', 0, 1, 'R');

    $pdf->Ln(15);

    // Conditions
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->Cell(0, 7, 'CONDITIONS', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 5, "• Validité du devis : 30 jours\n• Acompte de 30% à la commande\n• Solde à régler avant l'événement", 0, 'L');

    $pdf->Ln(10);

    // Signature
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 7, 'Signature client (précédée de "Bon pour accord") :', 0, 0, 'L');
    $pdf->Cell(90, 7, 'Pour Innov\'Events :', 0, 1, 'L');
    $pdf->Ln(20);
    $pdf->Cell(90, 7, '........................................', 0, 0, 'L');
    $pdf->Cell(90, 7, 'Chloé Dubois - Directrice', 0, 1, 'L');

    // Nom du fichier
    $filename = 'Devis_' . str_pad($quote['id'], 5, '0', STR_PAD_LEFT) . '.pdf';

    // Log MongoDB - Génération PDF devis
    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('GENERATION_DEVIS_PDF', 'quote', (int)$quote['id'], $authUserId, [
        'id_evenement' => (int)$quote['event_id']
    ]);

    // Output selon le mode
    if ($outputMode === 'string') {
        // Retourner le contenu PDF en string (pour l'envoi par email)
        echo $pdf->Output($filename, 'S');
    } else {
        // Téléchargement direct
        $pdf->Output($filename, 'D');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}