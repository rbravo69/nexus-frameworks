<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Exception\DuplicateCapabilityException;
use Nexus\Exception\UnknownCapabilityException;

final class CapabilityCatalog
{
    /** @var array<string, CapabilityDefinition> */
    private array $definitions = [];

    public static function official(): self
    {
        return (new self())->add(new CapabilityDefinition(
            name: 'redis',
            package: 'nexus/redis',
            provider: 'Nexus\\Redis\\RedisCapability',
        ));
    }

    public function add(CapabilityDefinition $definition): self
    {
        if (isset($this->definitions[$definition->name])) {
            throw DuplicateCapabilityException::named($definition->name);
        }

        $this->definitions[$definition->name] = $definition;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function get(string $name): CapabilityDefinition
    {
        return $this->definitions[$name] ?? throw UnknownCapabilityException::named($name);
    }

    /** @return array<string, CapabilityDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }
}
