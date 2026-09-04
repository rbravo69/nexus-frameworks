<?php

declare(strict_types=1);

namespace Nexus\Auth;

interface PasswordResetTokenRepositoryInterface
{
    public function store(int|string $identifier, string $tokenHash, \DateTimeImmutable $expiresAt): void;

    public function retrieveHash(int|string $identifier): ?string;

    public function expiresAt(int|string $identifier): ?\DateTimeImmutable;

    public function delete(int|string $identifier): void;
}
