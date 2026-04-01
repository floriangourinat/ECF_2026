<?php
/**
 * API Endpoint: Connexion utilisateur (version orientée objet)
 * backend/api/auth/login.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../../config/database.php';
require_once '../../services/MongoLogger.php';
require_once '../../services/JwtService.php';
require_once '../../services/Auth/UserRepository.php';
require_once '../../services/Auth/LoginValidator.php';
require_once '../../services/Auth/LoginService.php';

$jwtConfig = require '../../config/jwt.php';
$database = new Database();
$db = $database->getConnection();

$validator = new LoginValidator();
$userRepository = new UserRepository($db);
$logger = new MongoLogger();
$jwtService = new JwtService($jwtConfig['secret'], $jwtConfig['issuer']);
$loginService = new LoginService($userRepository, $logger, $jwtService, (int)$jwtConfig['ttl_seconds']);

try {
    $data = json_decode(file_get_contents('php://input'));
    $credentials = $validator->validate($data);
    $response = $loginService->login($credentials['email'], $credentials['password']);

    http_response_code(200);
    echo json_encode($response);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    http_response_code($e->getCode() ?: 401);
    echo json_encode(['message' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur serveur.']);
}