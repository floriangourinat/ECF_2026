<?php

class LoginValidator
{
    public function validate(?object $data): array
    {
        if (!$data || empty($data->email) || empty($data->password)) {
            throw new InvalidArgumentException("Données incomplètes. Email et mot de passe requis.");
        }

        $email = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new InvalidArgumentException("Format d'email invalide.");
        }

        return [
            'email' => $email,
            'password' => (string)$data->password,
        ];
    }
}