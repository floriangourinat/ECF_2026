<?php

require_once __DIR__ . '/../services/JwtService.php';

function getAuthorizationHeader(): ?string
{
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return trim($headers['Authorization']);
        }
        if (isset($headers['authorization'])) {
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
 * Vérifie que l'utilisateur est authentifié.
 * Optionnel : restreindre à certains rôles.
 * Retourne le payload du JWT si OK.
 */
function require_auth(array $allowedRoles = []): array
{
    $cfg = require __DIR__ . '/../config/jwt.php';
    $jwt = new JwtService($cfg['secret'], $cfg['issuer']);

    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['message' => 'Non authentifié. Token manquant.']);
        exit;
    }

    try {
        $payload = $jwt->decode($token);
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['message' => 'Non authentifié. ' . $e->getMessage()]);
        exit;
    }

    if (!empty($allowedRoles)) {
        $role = $payload['role'] ?? null;
        if (!$role || !in_array($role, $allowedRoles, true)) {
            http_response_code(403);
            echo json_encode(['message' => 'Accès refusé.']);
            exit;
        }
    }

    return $payload;
}
