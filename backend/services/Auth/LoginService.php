<?php

class LoginService
{
    /**
     * Hash factice pour limiter les écarts de timing quand l'email n'existe pas.
     * Mot de passe: "invalid-password-placeholder"
     */
    private const DUMMY_PASSWORD_HASH = '$2y$10$9gA6f47yho8z5CIjM3s5be0r0EnqwyI4ezgmO6ijLJrVN6a8GN28m';

    public function __construct(
        private UserRepository $userRepository,
        private MongoLogger $logger,
        private JwtService $jwtService,
        private LoginRateLimiter $rateLimiter,
        private int $jwtTtl
    ) {
    }

    public function login(string $email, string $password, string $ipAddress): array
    {
        if ($this->rateLimiter->isBlocked($email, $ipAddress)) {
            $this->delayOnFailure();
            throw new RuntimeException('Trop de tentatives. Réessayez plus tard.', 429);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            password_verify($password, self::DUMMY_PASSWORD_HASH);
            $this->handleFailedAuth($email, $ipAddress);
        }

        if (!password_verify($password, $user['password'])) {
            $this->handleFailedAuth($email, $ipAddress);
        }

        // Contrôles laissés après password_verify pour réduire les écarts de timing.
        if (!(bool)$user['is_active'] || (int)$user['email_verified'] !== 1) {
            $this->handleFailedAuth($email, $ipAddress);
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash !== false) {
                $this->userRepository->updatePasswordHash((int)$user['id'], $newHash);
            }
        }

        $this->rateLimiter->clear($email, $ipAddress);

        $this->logger->log('CONNEXION_REUSSIE', 'user', (int)$user['id'], (int)$user['id'], [
            'ip_address' => $ipAddress
        ]);

        $token = $this->jwtService->encode([
            'sub' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'jti' => bin2hex(random_bytes(16)),
            'nbf' => time()
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

    private function handleFailedAuth(string $email, string $ipAddress): void
    {
        $this->rateLimiter->hit($email, $ipAddress);
        $this->logger->log('CONNEXION_ECHOUEE', 'user', null, null, [
            'email' => $email,
            'ip_address' => $ipAddress
        ]);
        $this->delayOnFailure();
        throw new RuntimeException('Identifiants incorrects.', 401);
    }

    private function delayOnFailure(): void
    {
        $delayMicroseconds = random_int(180000, 420000);
        usleep($delayMicroseconds);
    }
}