<?php

declare(strict_types=1);

namespace Nexus\Cache;

use DateInterval;
use DateTimeImmutable;

final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expiresAt: ?int}> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): void
    {
        $seconds = $this->seconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            $this->delete($key);
            return;
        }

        $this->items[$key] = [
            'value' => $value,
            'expiresAt' => $seconds === null ? null : time() + $seconds,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }

    public function has(string $key): bool
    {
        if (!isset($this->items[$key])) {
            return false;
        }

        $expiresAt = $this->items[$key]['expiresAt'];
        if ($expiresAt !== null && $expiresAt <= time()) {
            unset($this->items[$key]);
            return false;
        }

        return true;
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function remember(string $key, int|DateInterval|null $ttl, callable $resolver): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $resolver();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function seconds(int|DateInterval|null $ttl): ?int
    {
        if ($ttl === null || is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable('@0');
        return $now->add($ttl)->getTimestamp();
    }
}
