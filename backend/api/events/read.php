<?php
/**
 * API Endpoint: Récupération de la liste des événements
 * Méthode: GET
 * URL: /api/events/read.php
 */

// Configuration des en-têtes HTTP (CORS et format JSON)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclusion de la connexion à la base de données
// Ajustement du chemin pour remonter vers 'config'
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Méthode non autorisée. Utilisez GET."]);
    exit;
}

try {
    // Requête SQL pour récupérer les événements avec les informations du client associé
    // Utilisation d'un LEFT JOIN pour inclure les événements même si le client a été supprimé
    $query = "SELECT 
                e.id, 
                e.nom_evenement, 
                e.date_evenement, 
                e.lieu, 
                e.type_evenement, 
                c.nom AS client_nom, 
                c.prenom AS client_prenom,
                c.email AS client_email
              FROM events e
              LEFT JOIN clients c ON e.client_id = c.id
              ORDER BY e.date_evenement ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $num = $stmt->rowCount();

    // Vérification s'il y a des résultats
    if ($num > 0) {
        $events_arr = array();
        $events_arr["records"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extraction des données de la ligne pour les rendre accessibles par nom de variable
            extract($row);

            $event_item = array(
                "id" => $id,
                "nom_evenement" => $nom_evenement,
                "date_evenement" => $date_evenement,
                "lieu" => $lieu,
                "type_evenement" => $type_evenement,
                "client_nom" => $client_nom,
                "client_prenom" => $client_prenom,
                "client_email" => $client_email
            );

            array_push($events_arr["records"], $event_item);
        }

        // Réponse 200 OK avec les données JSON
        http_response_code(200);
        echo json_encode($events_arr);
    } else {
        // Aucun événement trouvé, on renvoie un tableau vide
        http_response_code(200);
        echo json_encode(["records" => []]);
    }

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    http_response_code(500);
    echo json_encode(["message" => "Erreur lors de la lecture des événements.", "error" => $e->getMessage()]);
}
?>