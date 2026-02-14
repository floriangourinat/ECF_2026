<?php
/**
 * API: Modérer un avis (approuver/rejeter)
 * PUT /api/reviews/update_status.php
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
$reviewedBy = (int)$payload['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit();
}

$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($data['status'], $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide (pending, approved, rejected)']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmtCols = $db->prepare('SHOW COLUMNS FROM reviews');
    $stmtCols->execute();
    $columns = array_column($stmtCols->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $sql = 'UPDATE reviews SET status = :status';
    $params = [
        ':status' => $data['status'],
        ':id' => (int)$data['id']
    ];

    if (in_array('reviewed_by', $columns, true)) {
        $sql .= ', reviewed_by = :reviewed_by';
        $params[':reviewed_by'] = $reviewedBy;
    }

    if (in_array('updated_at', $columns, true)) {
        $sql .= ', updated_at = NOW()';
    }

    $sql .= ' WHERE id = :id';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Avis non trouvé']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Avis modéré avec succès'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
