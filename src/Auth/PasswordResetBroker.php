<?php

declare(strict_types=1);

namespace Nexus\Auth;

final readonly class PasswordResetBroker
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokens,
        private int $ttlMinutes = 60,
    ) {
        if ($ttlMinutes < 1) {
            throw new \InvalidArgumentException('Password reset TTL must be at least one minute.');
        }
    }

    public function issue(int|string $identifier): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d minutes', $this->ttlMinutes));
        $this->tokens->store($identifier, $hash, $expiresAt);

        return $token;
    }

    public function validate(int|string $identifier, string $token): bool
    {
        $hash = $this->tokens->retrieveHash($identifier);
        $expiresAt = $this->tokens->expiresAt($identifier);

        if ($hash === null || $expiresAt === null || $expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        return hash_equals($hash, hash('sha256', $token));
    }

    public function consume(int|string $identifier, string $token): bool
    {
        if (!$this->validate($identifier, $token)) {
            return false;
        }

        $this->tokens->delete($identifier);

        return true;
    }
}
