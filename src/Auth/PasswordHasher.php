<?php

declare(strict_types=1);

namespace Nexus\Auth;

final readonly class PasswordHasher
{
    /** @param array<string, mixed> $options */
    public function hash(string $password, string|int|null $algorithm = PASSWORD_DEFAULT, array $options = []): string
    {
        if ($password === '') {
            throw new \InvalidArgumentException('Password cannot be empty.');
        }

        $hash = password_hash($password, $algorithm, $options);

        if (!is_string($hash)) {
            throw new \RuntimeException('Unable to hash password.');
        }

        return $hash;
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /** @param array<string, mixed> $options */
    public function needsRehash(string $hash, string|int|null $algorithm = PASSWORD_DEFAULT, array $options = []): bool
    {
        return password_needs_rehash($hash, $algorithm, $options);
    }
}
