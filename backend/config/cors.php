<?php
/**
 * Configuration CORS centralisée
 * Autorise les requêtes depuis localhost (tous ports) en développement
 */

$allowed_origins = [
    'http://localhost:4200',   // Frontend web Angular
    'http://localhost:4300',   // App mobile Ionic
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Autoriser les origines localhost en développement
if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:4200');
}

header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
