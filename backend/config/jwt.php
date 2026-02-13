<?php
/**
 * Configuration JWT
 * IMPORTANT : en prod, mettre le secret en variable d'environnement.
 */
return [
    'secret' => 'CHANGE_ME_SUPER_SECRET_64CHARS_MINIMUM__________INNOVEVENTS',
    'issuer' => 'innovevents-api',
    'ttl_seconds' => 86400 // 24h
];
