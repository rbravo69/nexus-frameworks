<?php

declare(strict_types=1);

namespace Nexus\Auth;

interface RememberTokenRepositoryInterface
{
    public function store(int|string $identifier, string $selector, string $validatorHash, \DateTimeImmutable $expiresAt): void;

    /** @return array{identifier:int|string, validator_hash:string, expires_at:\DateTimeImmutable}|null */
    public function retrieve(string $selector): ?array;

    public function delete(string $selector): void;

    public function deleteForUser(int|string $identifier): void;
}
