<?php
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$newMessage = isset($data['message']) ? trim((string)$data['message']) : '';

if ($newMessage === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide']);
    exit();
}

if (mb_strlen($newMessage) < 10 || mb_strlen($newMessage) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le message doit contenir entre 10 et 500 caractères']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $createTableSql = "CREATE TABLE IF NOT EXISTS app_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(120) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($createTableSql);

    $sql = "INSERT INTO app_settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";

    $stmt = $db->prepare($sql);
    $settingKey = 'quote_success_message';
    $stmt->bindParam(':setting_key', $settingKey);
    $stmt->bindParam(':setting_value', $newMessage);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Message de remerciement mis à jour',
        'data' => [
            'quote_success_message' => $newMessage
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
