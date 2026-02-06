<?php
/**
 * API: Upload image événement
 * POST /api/events/upload_image.php
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

// Vérifier qu'un fichier a été envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune image valide envoyée']);
    exit();
}

$file = $_FILES['image'];
$eventId = $_POST['event_id'] ?? null;

// Validation du type de fichier
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, WebP ou GIF.']);
    exit();
}

// Validation de la taille (max 5 Mo)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image trop volumineuse. Maximum 5 Mo.']);
    exit();
}

// Créer le dossier uploads s'il n'existe pas
$uploadDir = '../../uploads/events/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Générer un nom unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'event_' . ($eventId ?? uniqid()) . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload']);
    exit();
}

// Chemin relatif pour la BDD
$relativePath = '/uploads/events/' . $filename;

// Si event_id fourni, mettre à jour la BDD
if ($eventId) {
    require_once '../../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Supprimer l'ancienne image si elle existe
        $stmt = $db->prepare("SELECT image_path FROM events WHERE id = :id");
        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event && $event['image_path']) {
            $oldFile = '../../' . ltrim($event['image_path'], '/');
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Mettre à jour le chemin de l'image
        $stmt = $db->prepare("UPDATE events SET image_path = :path WHERE id = :id");
        $stmt->execute([':path' => $relativePath, ':id' => $eventId]);
        
    } catch (PDOException $e) {
        // Continuer même si la mise à jour échoue
    }
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Image uploadée avec succès',
    'image_path' => $relativePath
]);