<?php

class JwtService
{
    private string $secret;
    private string $issuer;

    public function __construct(string $secret, string $issuer = 'innovevents-api')
    {
        $this->secret = $secret;
        $this->issuer = $issuer;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function encode(array $payload, int $ttlSeconds): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $now = time();
        $payload = array_merge([
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $ttlSeconds
        ], $payload);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Token invalide.');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $headerJson = $this->base64UrlDecode($headerEncoded);
        $payloadJson = $this->base64UrlDecode($payloadEncoded);

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!$header || !$payload || ($header['alg'] ?? '') !== 'HS256') {
            throw new Exception('Token invalide.');
        }

        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret, true);
        $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            throw new Exception('Signature invalide.');
        }

        if (!isset($payload['exp']) || time() > (int)$payload['exp']) {
            throw new Exception('Token expiré.');
        }

        return $payload;
    }
}
