<?php
/**
 * Configuration SMTP de l'application Innov'Events.
 *
 * Les valeurs sont lues depuis les variables d'environnement Docker.
 * Cela permet de conserver les identifiants SMTP hors du dépôt GitHub.
 *
 * En local, si aucune variable n'est définie, la configuration retombe
 * sur Mailhog afin de conserver un environnement de développement simple.
 */

return [
    'host' => getenv('SMTP_HOST') ?: 'mailhog',
    'port' => (int)(getenv('SMTP_PORT') ?: 1025),
    'username' => getenv('SMTP_USERNAME') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
    'encryption' => getenv('SMTP_ENCRYPTION') ?: '',
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'contact@innovevents-app.fr',
    'from_name' => getenv('SMTP_FROM_NAME') ?: "Innov'Events"
];