<?php

declare(strict_types=1);

namespace Nexus\Contracts;

interface ConfigurationInterface
{
    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    /** @return array<string, mixed> */
    public function all(): array;
}
