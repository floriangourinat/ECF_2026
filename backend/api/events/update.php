<?php
/**
 * API: Modifier un événement
 * PUT /api/events/update.php
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

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID événement requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer l'vénement actuel
    $stmtCurrent = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmtCurrent->execute([':id' => $data['id']]);
    $currentEvent = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

    if (!$currentEvent) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    // Valeurs à mettre à jour (garder l'existant si non fourni)
    $name = isset($data['name']) ? htmlspecialchars(strip_tags($data['name'])) : $currentEvent['name'];
    $description = isset($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : $currentEvent['description'];
    $location = isset($data['location']) ? htmlspecialchars(strip_tags($data['location'])) : $currentEvent['location'];
    $attendees_count = isset($data['attendees_count']) ? (int)$data['attendees_count'] : $currentEvent['attendees_count'];
    $budget = isset($data['budget']) ? (float)$data['budget'] : $currentEvent['budget'];
    $status = isset($data['status']) ? $data['status'] : $currentEvent['status'];
    
    // Gérer event_type (peut venir de type_id ou event_type)
    $event_type = $currentEvent['event_type'];
    if (isset($data['type_id']) && !empty($data['type_id'])) {
        $stmtType = $db->prepare("SELECT name FROM event_types WHERE id = :id");
        $stmtType->execute([':id' => $data['type_id']]);
        $typeRow = $stmtType->fetch(PDO::FETCH_ASSOC);
        if ($typeRow) {
            $event_type = $typeRow['name'];
        }
    } elseif (isset($data['event_type'])) {
        $event_type = $data['event_type'];
    }
    
    // Gérer theme (peut venir de theme_id ou theme)
    $theme = $currentEvent['theme'];
    if (isset($data['theme_id']) && !empty($data['theme_id'])) {
        $stmtTheme = $db->prepare("SELECT name FROM themes WHERE id = :id");
        $stmtTheme->execute([':id' => $data['theme_id']]);
        $themeRow = $stmtTheme->fetch(PDO::FETCH_ASSOC);
        if ($themeRow) {
            $theme = $themeRow['name'];
        }
    } elseif (isset($data['theme'])) {
        $theme = $data['theme'];
    }

    if (empty($event_type)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type d\'événement requis']);
        exit();
    }

    if (empty($theme)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thème requis']);
        exit();
    }
    
    // Gérer les dates
    if (isset($data['event_date']) && !empty($data['event_date'])) {
        $start_date = $data['event_date'];
        $end_date = $data['event_date'];
    } else {
        $start_date = isset($data['start_date']) ? $data['start_date'] : $currentEvent['start_date'];
        $end_date = isset($data['end_date']) ? $data['end_date'] : $currentEvent['end_date'];
    }

    $stmt = $db->prepare("
        UPDATE events 
        SET name = :name,
            description = :description,
            start_date = :start_date,
            end_date = :end_date,
            location = :location,
            event_type = :event_type,
            theme = :theme,
            status = :status,
            attendees_count = :attendees_count,
            budget = :budget,
            is_visible = :is_visible,
            updated_at = NOW()
        WHERE id = :id
    ");

    $is_visible = isset($data['is_visible']) ? (int)$data['is_visible'] : $currentEvent['is_visible'];

    $stmt->execute([
        ':id' => $data['id'],
        ':name' => $name,
        ':description' => $description,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':location' => $location,
        ':event_type' => $event_type,
        ':theme' => $theme,
        ':status' => $status,
        ':attendees_count' => $attendees_count,
        ':budget' => $budget,
        ':is_visible' => $is_visible
    ]);

    http_response_code(200);

    if (isset($data['status']) && $data['status'] !== $currentEvent['status']) {
        require_once '../../services/MongoLogger.php';
        $logger = new MongoLogger();
        $logger->log('MODIFICATION_STATUT_EVENEMENT', 'event', (int)$data['id'], null, [
            'id' => (int)$data['id'],
            'ancien_statut' => $currentEvent['status'],
            'nouveau_statut' => $data['status']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Événement modifié avec succès'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
