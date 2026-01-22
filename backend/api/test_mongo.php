<?php
// backend/api/test_mongo.php
header("Content-Type: application/json; charset=UTF-8");

// On inclut le fichier de connexion qu'on a créé juste avant
include_once '../config/database_mongo.php';

try {
    $database = new DatabaseMongo();
    $db = $database->getConnection();

    // On crée une collection 'logs' et on ajoute un document
    $collection = $db->logs;
    $result = $collection->insertOne([
        'message' => 'Test de connexion MongoDB réussi !',
        'date' => date('Y-m-d H:i:s'),
        'auteur' => 'Admin'
    ]);

    echo json_encode([
        "message" => "Succès MongoDB !",
        "id_insere" => (string)$result->getInsertedId()
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>