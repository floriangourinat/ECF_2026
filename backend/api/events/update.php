<?php
/**
 * API : Modifier un événement
 * Route : PUT /api/events/update.php
 *
 * Cette route met à jour les informations principales d'un événement.
 * Elle gère également la suppression de l'image associée lorsque le front-end
 * envoie explicitement une valeur vide pour le champ image_path.
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
require_once '../../middleware/auth.php';

$payload = require_auth(['admin']);
$userId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit();
}

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID événement requis']);
    exit();
}

/**
 * Supprime physiquement une image d'événement stockée dans le dossier uploads.
 *
 * La fonction limite volontairement la suppression au dossier /uploads/events/
 * afin d'éviter toute suppression de fichier en dehors du répertoire prévu.
 */
function deleteEventImageFile(?string $imagePath): void
{
    if (empty($imagePath)) {
        return;
    }

    $path = parse_url($imagePath, PHP_URL_PATH);
    $path = $path ?: $imagePath;

    if (!str_starts_with($path, '/uploads/events/')) {
        return;
    }

    $uploadsDirectory = realpath(__DIR__ . '/../../uploads/events');

    if ($uploadsDirectory === false) {
        return;
    }

    $filename = basename($path);
    $absolutePath = $uploadsDirectory . DIRECTORY_SEPARATOR . $filename;
    $realFilePath = realpath($absolutePath);

    if (
        $realFilePath !== false &&
        str_starts_with($realFilePath, $uploadsDirectory) &&
        is_file($realFilePath)
    ) {
        unlink($realFilePath);
    }
}

/**
 * Normalise le chemin image reçu depuis le front-end.
 *
 * - null ou chaîne vide : suppression de l'image.
 * - /uploads/events/... : chemin accepté.
 * - autre valeur : rejetée pour éviter un chemin non maîtrisé.
 */
function normalizeEventImagePath($imagePath): ?string
{
    if ($imagePath === null || $imagePath === '') {
        return null;
    }

    if (!is_string($imagePath)) {
        return null;
    }

    $imagePath = trim($imagePath);

    if ($imagePath === '') {
        return null;
    }

    if (!str_starts_with($imagePath, '/uploads/events/')) {
        throw new InvalidArgumentException('Chemin image invalide');
    }

    return $imagePath;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupération de l'événement actuel pour conserver les valeurs non modifiées.
    $stmtCurrent = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmtCurrent->execute([':id' => $data['id']]);
    $currentEvent = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

    if (!$currentEvent) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
        exit();
    }

    // Valeurs principales à mettre à jour.
    $name = isset($data['name'])
        ? htmlspecialchars(strip_tags($data['name']))
        : $currentEvent['name'];

    $description = isset($data['description'])
        ? htmlspecialchars(strip_tags($data['description']))
        : $currentEvent['description'];

    $location = isset($data['location'])
        ? htmlspecialchars(strip_tags($data['location']))
        : $currentEvent['location'];

    $attendees_count = isset($data['attendees_count'])
        ? (int)$data['attendees_count']
        : (int)$currentEvent['attendees_count'];

    $budget = isset($data['budget'])
        ? (float)$data['budget']
        : (float)$currentEvent['budget'];

    $status = isset($data['status'])
        ? $data['status']
        : $currentEvent['status'];

    // Gestion du type d'événement : peut venir de type_id ou directement de event_type.
    $event_type = $currentEvent['event_type'];

    if (isset($data['type_id']) && !empty($data['type_id'])) {
        $stmtType = $db->prepare("SELECT name FROM event_types WHERE id = :id");
        $stmtType->execute([':id' => $data['type_id']]);
        $typeRow = $stmtType->fetch(PDO::FETCH_ASSOC);

        if ($typeRow) {
            $event_type = $typeRow['name'];
        }
    } elseif (isset($data['event_type'])) {
        $event_type = htmlspecialchars(strip_tags($data['event_type']));
    }

    // Gestion du thème : peut venir de theme_id ou directement de theme.
    $theme = $currentEvent['theme'];

    if (isset($data['theme_id']) && !empty($data['theme_id'])) {
        $stmtTheme = $db->prepare("SELECT name FROM themes WHERE id = :id");
        $stmtTheme->execute([':id' => $data['theme_id']]);
        $themeRow = $stmtTheme->fetch(PDO::FETCH_ASSOC);

        if ($themeRow) {
            $theme = $themeRow['name'];
        }
    } elseif (isset($data['theme'])) {
        $theme = htmlspecialchars(strip_tags($data['theme']));
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

    // Gestion des dates.
    if (isset($data['event_date']) && !empty($data['event_date'])) {
        $start_date = $data['event_date'];
        $end_date = $data['event_date'];
    } else {
        $start_date = isset($data['start_date'])
            ? $data['start_date']
            : $currentEvent['start_date'];

        $end_date = isset($data['end_date'])
            ? $data['end_date']
            : $currentEvent['end_date'];
    }

    $is_visible = isset($data['is_visible'])
        ? (int)$data['is_visible']
        : (int)$currentEvent['is_visible'];

    /*
     * Gestion de l'image :
     * - Si image_path n'est pas envoyé, on conserve l'image actuelle.
     * - Si image_path est envoyé vide, on supprime l'image en base et le fichier physique.
     * - Si image_path change, on remplace le chemin et on supprime l'ancienne image.
     */
    $imagePathWasProvided = array_key_exists('image_path', $data);
    $image_path = $currentEvent['image_path'];

    if ($imagePathWasProvided) {
        $image_path = normalizeEventImagePath($data['image_path']);

        if ($image_path !== $currentEvent['image_path']) {
            deleteEventImageFile($currentEvent['image_path']);
        }
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
            image_path = :image_path,
            is_visible = :is_visible,
            updated_at = NOW()
        WHERE id = :id
    ");

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
        ':image_path' => $image_path,
        ':is_visible' => $is_visible
    ]);

    if (isset($data['status']) && $data['status'] !== $currentEvent['status']) {
        require_once '../../services/MongoLogger.php';

        $logger = new MongoLogger();
        $logger->log('MODIFICATION_STATUT_EVENEMENT', 'event', (int)$data['id'], $userId, [
            'id' => (int)$data['id'],
            'ancien_statut' => $currentEvent['status'],
            'nouveau_statut' => $data['status']
        ]);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Événement modifié avec succès',
        'image_path' => $image_path
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}