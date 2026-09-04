<?php

declare(strict_types=1);

namespace Nexus\Session;

interface SessionInterface
{
    public function start(): void;

    public function close(): void;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;

    public function regenerate(bool $deleteOldSession = false): void;

    public function invalidate(): void;

    public function flash(string $key, mixed $value): void;

    public function ageFlashData(): void;

    /** @return array<string, mixed> */
    public function all(): array;
}
