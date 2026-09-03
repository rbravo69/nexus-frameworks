<?php

declare(strict_types=1);

namespace Nexus\Redis;

interface RedisClientInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value, ?int $ttlSeconds = null): void;

    public function delete(string $key): void;

    public function exists(string $key): bool;

    public function deleteByPrefix(string $prefix): void;

    public function acquireLock(string $key, string $token, int $ttlMilliseconds): bool;

    public function releaseLock(string $key, string $token): bool;
}
