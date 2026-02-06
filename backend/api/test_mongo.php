<?php
header('Content-Type: application/json');

require_once '../services/MongoLogger.php';

try {
    $logger = new MongoLogger();
    
    // Test d'écriture
    $result = $logger->log('test', 'debug', 1, 1, ['message' => 'Test MongoDB']);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'MongoDB fonctionne ! Log créé avec succès.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Échec de création du log. MongoDB désactivé ou erreur de connexion.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}