<?php

declare(strict_types=1);

namespace Nexus\Configuration;

use Nexus\Contracts\ConfigurationInterface;

final readonly class Configuration implements ConfigurationInterface
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values = [])
    {
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->values;
        }

        $value = $this->values;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->values;
    }
}
