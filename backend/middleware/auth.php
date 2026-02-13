<?php
/**
 * Middleware: Authentification JWT
 * - Vérifie le token Bearer
 * - Bloque l'accès si must_change_password = 1 (sauf exception)
 */

require_once __DIR__ . '/../services/JwtService.php';
require_once __DIR__ . '/../config/database.php';

function getAuthorizationHeader(): ?string
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (!empty($headers['Authorization'])) {
            return trim($headers['Authorization']);
        }
        if (!empty($headers['authorization'])) {
            return trim($headers['authorization']);
        }
    }

    return null;
}

function getBearerToken(): ?string
{
    $header = getAuthorizationHeader();
    if (!$header) return null;

    if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Auth obligatoire + verrouillage mot de passe temporaire
 */
function require_auth(array $allowedRoles = [], bool $allowPasswordChange = false): array
{
    $cfg = require __DIR__ . '/../config/jwt.php';
    $jwt = new JwtService($cfg['secret'], $cfg['issuer']);

    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(["message" => "Token manquant."]);
        exit;
    }

    try {
        $payload = $jwt->decode($token);
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["message" => "Token invalide."]);
        exit;
    }

    $role = $payload['role'] ?? null;
    if (!empty($allowedRoles) && (!$role || !in_array($role, $allowedRoles, true))) {
        http_response_code(403);
        echo json_encode(["message" => "Accès refusé."]);
        exit;
    }

    // ID utilisateur depuis JWT (standard = sub)
    $userId = (int)($payload['sub'] ?? $payload['user_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(["message" => "Token invalide."]);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("SELECT must_change_password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Utilisateur non trouvé."]);
            exit;
        }

        if ((int)$user['must_change_password'] === 1 && !$allowPasswordChange) {
            http_response_code(403);
            echo json_encode([
                "message" => "Changement de mot de passe requis.",
                "code" => "PASSWORD_CHANGE_REQUIRED"
            ]);
            exit;
        }

        // Normalisation pratique (pour le reste du code)
        $payload['user_id'] = $userId;

        return $payload;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erreur serveur."]);
        exit;
    }
}
