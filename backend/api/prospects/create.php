<?php
// Headers CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Méthode non autorisée']);
    exit();
}

// Connexion à la base de données
require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validation des champs requis
$requiredFields = ['company_name', 'last_name', 'first_name', 'email', 'phone', 'event_type', 'planned_date', 'estimated_participants', 'needs_description'];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Champs requis manquants',
        'missing_fields' => $missingFields
    ]);
    exit();
}

// Validation de l'email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Format d\'email invalide']);
    exit();
}

// Validation du nombre de participants
if (!is_numeric($data['estimated_participants']) || $data['estimated_participants'] < 1) {
    http_response_code(400);
    echo json_encode(['message' => 'Le nombre de participants doit être un nombre positif']);
    exit();
}

// Validation de la date (doit être dans le futur)
$plannedDate = strtotime($data['planned_date']);
if ($plannedDate === false || $plannedDate < strtotime('today')) {
    http_response_code(400);
    echo json_encode(['message' => 'La date prévue doit être dans le futur']);
    exit();
}

// Nettoyer les données
$company_name = htmlspecialchars(strip_tags($data['company_name']));
$last_name = htmlspecialchars(strip_tags($data['last_name']));
$first_name = htmlspecialchars(strip_tags($data['first_name']));
$email = htmlspecialchars(strip_tags($data['email']));
$phone = htmlspecialchars(strip_tags($data['phone']));
$location = htmlspecialchars(strip_tags($data['location'] ?? ''));
$event_type = htmlspecialchars(strip_tags($data['event_type']));
$planned_date = $data['planned_date'];
$estimated_participants = intval($data['estimated_participants']);
$needs_description = htmlspecialchars(strip_tags($data['needs_description']));

try {
    // Insérer le prospect
    $query = "INSERT INTO prospects (
        company_name, 
        last_name, 
        first_name, 
        email, 
        phone, 
        location, 
        event_type, 
        planned_date, 
        estimated_participants, 
        needs_description, 
        status,
        created_at
    ) VALUES (
        :company_name, 
        :last_name, 
        :first_name, 
        :email, 
        :phone, 
        :location, 
        :event_type, 
        :planned_date, 
        :estimated_participants, 
        :needs_description, 
        'to_contact',
        NOW()
    )";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_name', $company_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':event_type', $event_type);
    $stmt->bindParam(':planned_date', $planned_date);
    $stmt->bindParam(':estimated_participants', $estimated_participants);
    $stmt->bindParam(':needs_description', $needs_description);

    if ($stmt->execute()) {
        $prospect_id = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Votre demande de devis a été envoyée avec succès ! Notre équipe vous contactera dans les plus brefs délais.',
            'prospect_id' => $prospect_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Erreur lors de l\'enregistrement de la demande']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}
?>