<?php
/**
 * API Endpoint: Connexion utilisateur (version orientée objet)
 * backend/api/auth/login.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Méthode non autorisée.']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== 0) {
    http_response_code(415);
    echo json_encode(['message' => 'Content-Type non supporté.']);
    exit;
}

include_once '../../config/database.php';
require_once '../../services/MongoLogger.php';
require_once '../../services/JwtService.php';
require_once '../../services/Auth/UserRepository.php';
require_once '../../services/Auth/LoginValidator.php';
require_once '../../services/Auth/LoginRateLimiter.php';
require_once '../../services/Auth/LoginService.php';

$jwtConfig = require '../../config/jwt.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur serveur.']);
    exit;
}

$validator = new LoginValidator();
$userRepository = new UserRepository($db);
$logger = new MongoLogger();
$jwtService = new JwtService($jwtConfig['secret'], $jwtConfig['issuer']);
$rateLimiter = new LoginRateLimiter(maxAttempts: 5, windowSeconds: 900, blockSeconds: 900);
$loginService = new LoginService(
    userRepository: $userRepository,
    logger: $logger,
    jwtService: $jwtService,
    rateLimiter: $rateLimiter,
    jwtTtl: (int)$jwtConfig['ttl_seconds']
);

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

try {
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('Corps JSON invalide.');
    }

    $credentials = $validator->validate($data);
    $response = $loginService->login($credentials['email'], $credentials['password'], $ipAddress);

    http_response_code(200);
    echo json_encode($response);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    $code = $e->getCode();
    http_response_code(in_array($code, [401, 429], true) ? $code : 401);
    echo json_encode(['message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur serveur.']);
}