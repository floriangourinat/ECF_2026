<?php
/**
 * Configuration email
 * En dev: Mailhog (localhost:1025)
 * En prod: Remplacer par SMTP réel (Gmail, SendGrid, OVH...)
 */

return [
    'host' => 'mailhog',      // En prod: 'smtp.gmail.com' ou autre
    'port' => 1025,           // En prod: 587 (TLS) ou 465 (SSL)
    'username' => '',         // En prod: votre email
    'password' => '',         // En prod: mot de passe app
    'encryption' => '',       // En prod: 'tls' ou 'ssl'
    'from_email' => 'contact@innovevents.com',
    'from_name' => "Innov'Events"
];