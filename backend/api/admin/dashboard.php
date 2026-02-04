<?php
/**
 * API: Dashboard Admin - Données agrégées
 * GET /api/admin/dashboard.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Prochains événements (3)
    $stmtEvents = $db->prepare("
        SELECT e.id, e.name, e.start_date, e.location, e.status,
               c.company_name as client_company
        FROM events e
        LEFT JOIN clients c ON e.client_id = c.id
        WHERE e.start_date >= NOW() AND e.status NOT IN ('cancelled', 'completed')
        ORDER BY e.start_date ASC
        LIMIT 3
    ");
    $stmtEvents->execute();
    $upcomingEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    // 2. Notes récentes (5)
    $stmtNotes = $db->prepare("
        SELECT n.id, n.content, n.created_at, n.is_global,
               u.first_name, u.last_name,
               e.name as event_name
        FROM notes n
        JOIN users u ON n.author_id = u.id
        LEFT JOIN events e ON n.event_id = e.id
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmtNotes->execute();
    $recentNotes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Indicateurs clés
    // Clients actifs (avec au moins un événement en cours)
    $stmtActiveClients = $db->prepare("
        SELECT COUNT(DISTINCT c.id) as count
        FROM clients c
        JOIN events e ON c.id = e.client_id
        WHERE e.status IN ('accepted', 'in_progress')
    ");
    $stmtActiveClients->execute();
    $activeClients = $stmtActiveClients->fetch(PDO::FETCH_ASSOC)['count'];

    // Événements en brouillon
    $stmtDraftEvents = $db->prepare("
        SELECT COUNT(*) as count
        FROM events
        WHERE status = 'draft'
    ");
    $stmtDraftEvents->execute();
    $draftEvents = $stmtDraftEvents->fetch(PDO::FETCH_ASSOC)['count'];

    // Prospects à contacter
    $stmtProspects = $db->prepare("
        SELECT COUNT(*) as count
        FROM prospects
        WHERE status = 'to_contact'
    ");
    $stmtProspects->execute();
    $prospectsToContact = $stmtProspects->fetch(PDO::FETCH_ASSOC)['count'];

    // Total clients
    $stmtTotalClients = $db->prepare("SELECT COUNT(*) as count FROM clients");
    $stmtTotalClients->execute();
    $totalClients = $stmtTotalClients->fetch(PDO::FETCH_ASSOC)['count'];

    // Total événements
    $stmtTotalEvents = $db->prepare("SELECT COUNT(*) as count FROM events");
    $stmtTotalEvents->execute();
    $totalEvents = $stmtTotalEvents->fetch(PDO::FETCH_ASSOC)['count'];

    // Devis en attente
    $stmtPendingQuotes = $db->prepare("
        SELECT COUNT(*) as count
        FROM quotes
        WHERE status = 'pending'
    ");
    $stmtPendingQuotes->execute();
    $pendingQuotes = $stmtPendingQuotes->fetch(PDO::FETCH_ASSOC)['count'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'upcoming_events' => $upcomingEvents,
            'recent_notes' => $recentNotes,
            'stats' => [
                'active_clients' => (int)$activeClients,
                'draft_events' => (int)$draftEvents,
                'prospects_to_contact' => (int)$prospectsToContact,
                'total_clients' => (int)$totalClients,
                'total_events' => (int)$totalEvents,
                'pending_quotes' => (int)$pendingQuotes
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}