<?php
/**
 * API: Modifier une note
 * PUT /api/notes/update.php
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

$payload = require_auth(['admin', 'employee']);
$userId = (int)$payload['user_id'];
$userRole = $payload['role'] ?? '';

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id']) || empty($data['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et contenu requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtNote = $db->prepare('SELECT id, author_id, is_global FROM notes WHERE id = :id LIMIT 1');
    $stmtNote->execute([':id' => (int)$data['id']]);
    $note = $stmtNote->fetch(PDO::FETCH_ASSOC);

    if (!$note) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Note non trouvée']);
        exit();
    }

    $isOwner = (int)$note['author_id'] === $userId;
    $isAdmin = $userRole === 'admin';

    if (!$isAdmin && !$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit();
    }

    if ((int)$note['is_global'] === 1 && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Seul un admin peut modifier une note globale']);
        exit();
    }

    $stmt = $db->prepare('UPDATE notes SET content = :content WHERE id = :id');
    $stmt->execute([
        ':content' => htmlspecialchars(strip_tags(trim($data['content']))),
        ':id' => (int)$data['id']
    ]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('update', 'note', (int)$data['id'], $userId, [
        'is_global' => (bool)$note['is_global']
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Note modifiée']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
