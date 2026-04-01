<?php

class LoginService
{
    public function __construct(
        private UserRepository $userRepository,
        private MongoLogger $logger,
        private JwtService $jwtService,
        private int $jwtTtl
    ) {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $this->logFailedLogin($email);
            throw new RuntimeException("Identifiants incorrects.", 401);
        }

        if (!(bool)$user['is_active']) {
            throw new RuntimeException("Compte suspendu. Contactez l'administrateur.", 403);
        }

        if ((int)$user['email_verified'] !== 1) {
            throw new RuntimeException("Email non vérifié. Veuillez confirmer votre email avant de vous connecter.", 403);
        }

        if (!password_verify($password, $user['password'])) {
            $this->logFailedLogin($email);
            throw new RuntimeException("Identifiants incorrects.", 401);
        }

        $this->logger->log('CONNEXION_REUSSIE', 'user', (int)$user['id'], (int)$user['id'], [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $token = $this->jwtService->encode([
            'sub' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ], $this->jwtTtl);

        return [
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'last_name' => $user['last_name'],
                'first_name' => $user['first_name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'must_change_password' => (bool)$user['must_change_password']
            ]
        ];
    }

    private function logFailedLogin(string $email): void
    {
        $this->logger->log('CONNEXION_ECHOUEE', 'user', null, null, [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}