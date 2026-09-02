<?php

declare(strict_types=1);

namespace Nexus\Cache;

use DateInterval;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): void;

    public function delete(string $key): void;

    public function has(string $key): bool;

    public function clear(): void;

    /**
     * @template T
     * @param callable(): T $resolver
     * @return T
     */
    public function remember(string $key, int|DateInterval|null $ttl, callable $resolver): mixed;
}
