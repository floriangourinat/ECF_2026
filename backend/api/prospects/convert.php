<?php
/**
 * API: Convertir un prospect en client
 * POST /api/prospects/convert.php
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

if (empty($data['prospect_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID prospect requis']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer le prospect non converti
    $stmt = $db->prepare("SELECT * FROM prospects WHERE id = :id AND status != 'converted' LIMIT 1");
    $stmt->execute([':id' => $data['prospect_id']]);
    $prospect = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prospect) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Prospect non trouvé ou déjà converti']);
        exit();
    }

    $db->beginTransaction();

    // Vérifier si l'email existe déjà
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmtCheck->execute([':email' => $prospect['email']]);
    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // Cas demandé : déjà inscrit => considérer la conversion comme acceptée
        $stmtUpdate = $db->prepare("UPDATE prospects SET status = 'converted' WHERE id = :id");
        $stmtUpdate->execute([':id' => $data['prospect_id']]);

        $db->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Un utilisateur avec cet email existe déjà',
            'data' => [
                'user_id' => (int)$existingUser['id'],
                'client_id' => null,
                'temp_password' => null,
                'already_existing' => true
            ]
        ]);
        exit();
    }

    // Générer un mot de passe temporaire
    $tempPassword = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

    // Créer l'utilisateur
    $stmtUser = $db->prepare("
        INSERT INTO users (last_name, first_name, email, password, role, must_change_password, created_at)
        VALUES (:last_name, :first_name, :email, :password, 'client', 1, NOW())
    ");
    $stmtUser->execute([
        ':last_name' => $prospect['last_name'],
        ':first_name' => $prospect['first_name'],
        ':email' => $prospect['email'],
        ':password' => $hashedPassword
    ]);
    $userId = (int)$db->lastInsertId();

    // Créer le client
    $stmtClient = $db->prepare("
        INSERT INTO clients (user_id, company_name, phone, address, created_at)
        VALUES (:user_id, :company_name, :phone, :address, NOW())
    ");
    $stmtClient->execute([
        ':user_id' => $userId,
        ':company_name' => $prospect['company_name'],
        ':phone' => $prospect['phone'],
        ':address' => $prospect['location']
    ]);
    $clientId = (int)$db->lastInsertId();

    // Mettre à jour le statut du prospect
    $stmtUpdate = $db->prepare("UPDATE prospects SET status = 'converted' WHERE id = :id");
    $stmtUpdate->execute([':id' => $data['prospect_id']]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Prospect converti en client avec succès',
        'data' => [
            'user_id' => $userId,
            'client_id' => $clientId,
            'temp_password' => $tempPassword,
            'already_existing' => false
        ]
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
