<?php
/**
 * API: Créer une note
 * POST /api/notes/create.php
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

$isGlobal = !empty($data['is_global']) ? 1 : 0;

// Validation
if (empty($data['content']) || empty($data['author_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'author_id et content requis']);
    exit();
}

if (!$isGlobal && empty($data['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'event_id requis pour une note projet']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare(" 
        INSERT INTO notes (event_id, author_id, content, is_global, created_at)
        VALUES (:event_id, :author_id, :content, :is_global, NOW())
    ");

    if ($isGlobal) {
        $stmt->bindValue(':event_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':event_id', (int)$data['event_id'], PDO::PARAM_INT);
    }

    $stmt->bindValue(':author_id', (int)$data['author_id'], PDO::PARAM_INT);
    $stmt->bindValue(':content', htmlspecialchars(strip_tags($data['content'])), PDO::PARAM_STR);
    $stmt->bindValue(':is_global', $isGlobal, PDO::PARAM_INT);
    $stmt->execute();

    $noteId = $db->lastInsertId();

    // Récupérer la note avec les infos de l'auteur
    $stmtNote = $db->prepare("
        SELECT n.*, u.first_name, u.last_name
        FROM notes n
        JOIN users u ON n.author_id = u.id
        WHERE n.id = :id
    ");
    $stmtNote->execute([':id' => $noteId]);
    $note = $stmtNote->fetch(PDO::FETCH_ASSOC);

    // Log MongoDB
    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('create', 'note', $noteId, (int)$data['author_id'], [
        'event_id' => $isGlobal ? null : (int)$data['event_id'],
        'is_global' => (bool)$isGlobal
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Note créée avec succès',
        'data' => $note
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
