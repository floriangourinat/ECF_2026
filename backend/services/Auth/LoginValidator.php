<?php

class LoginValidator
{
    private const MAX_EMAIL_LENGTH = 254;
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 1024;

    public function validate(?object $data): array
    {
        if (!$data || !isset($data->email, $data->password)) {
            throw new InvalidArgumentException("Données incomplètes. Email et mot de passe requis.");
        }

        $emailRaw = trim((string)$data->email);
        $password = (string)$data->password;

        if ($emailRaw === '' || $password === '') {
            throw new InvalidArgumentException("Données incomplètes. Email et mot de passe requis.");
        }

        if (strlen($emailRaw) > self::MAX_EMAIL_LENGTH) {
            throw new InvalidArgumentException("Format d'email invalide.");
        }

        $passwordLength = strlen($password);
        if ($passwordLength < self::MIN_PASSWORD_LENGTH || $passwordLength > self::MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException("Mot de passe invalide.");
        }

        $email = filter_var(strtolower($emailRaw), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new InvalidArgumentException("Format d'email invalide.");
        }

        return [
            'email' => $email,
            'password' => $password,
        ];
    }
}