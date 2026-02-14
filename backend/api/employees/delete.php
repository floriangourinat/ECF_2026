<?php
/**
 * API: Supprimer un employé
 * DELETE /api/employees/delete.php
 */

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$payload = require_auth(['admin']);
$actingUserId = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID employé requis']);
    exit();
}

$targetId = (int)$data['id'];
if ($targetId === $actingUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtRole = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $stmtRole->execute([':id' => $targetId]);
    $user = $stmtRole->fetch(PDO::FETCH_ASSOC);

    if (!$user || !in_array($user['role'], ['admin', 'employee'], true)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
        exit();
    }

    if ($user['role'] === 'admin') {
        $stmtCheck = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND id != :id");
        $stmtCheck->execute([':id' => $targetId]);
        $adminCount = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['count'];

        if ($adminCount === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le dernier administrateur']);
            exit();
        }
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role IN ('admin', 'employee')");
    $stmt->execute([':id' => $targetId]);

    require_once '../../services/MongoLogger.php';
    $logger = new MongoLogger();
    $logger->log('SUPPRESSION_EMPLOYE', 'user', $targetId, $actingUserId, [
        'role_cible' => $user['role']
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Employé supprimé avec succès'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
