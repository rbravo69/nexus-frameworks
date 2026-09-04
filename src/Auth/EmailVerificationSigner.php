<?php

declare(strict_types=1);

namespace Nexus\Auth;

final readonly class EmailVerificationSigner
{
    public function __construct(private string $secret, private int $ttlMinutes = 60)
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('Email verification secret cannot be empty.');
        }

        if ($ttlMinutes < 1) {
            throw new \InvalidArgumentException('Email verification TTL must be at least one minute.');
        }
    }

    public function issue(int|string $identifier): string
    {
        $expires = time() + ($this->ttlMinutes * 60);
        $payload = (string) $identifier . '|' . $expires;
        $encoded = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $encoded, $this->secret);

        return $encoded . '.' . $signature;
    }

    public function validate(int|string $identifier, string $token): bool
    {
        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, '');

        if ($encoded === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $encoded, $this->secret);

        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $decoded = $this->base64UrlDecode($encoded);

        if ($decoded === null) {
            return false;
        }

        [$tokenIdentifier, $expires] = array_pad(explode('|', $decoded, 2), 2, '');

        return hash_equals((string) $identifier, $tokenIdentifier)
            && ctype_digit($expires)
            && (int) $expires >= time();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = (4 - (strlen($value) % 4)) % 4;
        $decoded = base64_decode(strtr($value . str_repeat('=', $padding), '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}
