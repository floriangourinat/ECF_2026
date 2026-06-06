<?php
/**
 * API : upload d'une image d'événement
 *
 * Cet endpoint permet à un administrateur d'envoyer une image liée à un événement.
 * L'upload est protégé par JWT, limité aux images autorisées et encadré par des
 * contrôles serveur sur le type MIME, la taille et le déplacement du fichier.
 */

require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit();
}

require_once __DIR__ . '/../../middleware/auth.php';

// L'upload d'une image d'événement est réservé à l'administrateur.
$currentUser = require_auth(['admin']);

// Vérifier qu'un fichier a bien été envoyé.
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Aucune image valide envoyée'
    ]);
    exit();
}

$file = $_FILES['image'];
$eventId = $_POST['event_id'] ?? null;

// Validation du type MIME réel du fichier.
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, WebP ou GIF.'
    ]);
    exit();
}

// Validation de la taille maximale : 5 Mo.
$maxSize = 5 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Image trop volumineuse. Maximum 5 Mo.'
    ]);
    exit();
}

// Création du dossier d'upload si nécessaire.
$uploadDir = __DIR__ . '/../../uploads/events/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Génération d'un nom de fichier contrôlé.
// L'extension est déduite du type MIME validé, pas seulement du nom d'origine.
$extensionsByMime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

$extension = $extensionsByMime[$fileType];
$filename = 'event_' . ($eventId ?: uniqid('', true)) . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Déplacement sécurisé du fichier uploadé.
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'upload'
    ]);
    exit();
}

// Chemin relatif utilisé par l'application.
$relativePath = '/uploads/events/' . $filename;

// Si un event_id est fourni, mise à jour de l'image associée à l'événement.
if ($eventId) {
    require_once __DIR__ . '/../../config/database.php';

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Récupération de l'ancienne image pour éviter de conserver un fichier inutile.
        $stmt = $db->prepare("SELECT image_path FROM events WHERE id = :id");
        $stmt->execute([
            ':id' => (int) $eventId
        ]);

        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event && !empty($event['image_path'])) {
            $oldFile = __DIR__ . '/../../' . ltrim($event['image_path'], '/');

            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Mise à jour du chemin de l'image dans la table events.
        $stmt = $db->prepare("
            UPDATE events
            SET image_path = :path, updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':path' => $relativePath,
            ':id' => (int) $eventId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Image uploadée, mais erreur lors de la mise à jour de l’événement'
        ]);
        exit();
    }
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Image uploadée avec succès',
    'image_path' => $relativePath
]);