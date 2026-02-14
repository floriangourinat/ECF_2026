<?php
/**
 * Configuration JWT
 */

$defaultSecret = 'CHANGE_ME_SUPER_SECRET_64CHARS_MINIMUM__________INNOVEVENTS';
$secret = getenv('JWT_SECRET') ?: $defaultSecret;

if ($secret === $defaultSecret) {
    error_log('WARNING: JWT_SECRET environment variable is not set. Using insecure default secret.');
}

return [
    'secret' => $secret,
    'issuer' => getenv('JWT_ISSUER') ?: 'innovevents-api',
    'ttl_seconds' => (int)(getenv('JWT_TTL_SECONDS') ?: 86400)
];
