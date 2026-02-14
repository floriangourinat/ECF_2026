<?php
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../../config/database.php';

$defaultMessage = 'Merci pour votre demande. Chloé vous recontactera dans les plus brefs délais pour discuter de votre projet.';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1";
    $stmt = $db->prepare($query);
    $settingKey = 'quote_success_message';
    $stmt->bindParam(':setting_key', $settingKey);
    $stmt->execute();

    $message = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => !empty($message) ? $message : $defaultMessage
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => true,
        'message' => $defaultMessage,
        'warning' => 'Valeur par défaut utilisée'
    ]);
}
