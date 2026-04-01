<?php

class LoginRateLimiter
{
    private string $storageFile;

    public function __construct(
        private int $maxAttempts = 5,
        private int $windowSeconds = 900,
        private int $blockSeconds = 900,
        ?string $storageDir = null
    ) {
        $dir = $storageDir ?? sys_get_temp_dir();
        $this->storageFile = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ecf_login_rate_limit.json';
    }

    public function isBlocked(string $email, string $ip): bool
    {
        $key = $this->buildKey($email, $ip);
        $now = time();

        return $this->withStore(function (array &$store) use ($key, $now): bool {
            $record = $store[$key] ?? null;
            if (!$record) {
                return false;
            }

            $blockedUntil = (int)($record['blocked_until'] ?? 0);
            if ($blockedUntil > $now) {
                return true;
            }

            if ($blockedUntil !== 0 && $blockedUntil <= $now) {
                unset($store[$key]);
            }

            return false;
        });
    }

    public function hit(string $email, string $ip): void
    {
        $key = $this->buildKey($email, $ip);
        $now = time();

        $this->withStore(function (array &$store) use ($key, $now): void {
            $record = $store[$key] ?? [
                'attempts' => [],
                'blocked_until' => 0,
            ];

            $attempts = array_values(array_filter(
                $record['attempts'],
                fn (int $timestamp): bool => ($timestamp >= ($now - $this->windowSeconds))
            ));

            $attempts[] = $now;
            $record['attempts'] = $attempts;

            if (count($attempts) >= $this->maxAttempts) {
                $record['blocked_until'] = $now + $this->blockSeconds;
                $record['attempts'] = [];
            }

            $store[$key] = $record;
        });
    }

    public function clear(string $email, string $ip): void
    {
        $key = $this->buildKey($email, $ip);

        $this->withStore(function (array &$store) use ($key): void {
            unset($store[$key]);
        });
    }

    private function buildKey(string $email, string $ip): string
    {
        return hash('sha256', $email . '|' . $ip);
    }

    private function withStore(callable $callback)
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $handle = fopen($this->storageFile, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Impossible d\'ouvrir le stockage du rate limiter.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Impossible de verrouiller le stockage du rate limiter.');
            }

            $content = stream_get_contents($handle);
            $store = [];

            if ($content !== false && trim($content) !== '') {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $store = $decoded;
                }
            }

            $result = $callback($store);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($store, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }
}