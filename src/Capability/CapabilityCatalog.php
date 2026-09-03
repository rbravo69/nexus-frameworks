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
        $provider = BundledCapability::class;
        $package = 'nexus/framework';
        $distribution = CapabilityDistribution::Bundled;

        return (new self())
            ->add(new CapabilityDefinition('database', $package, $provider, distribution: $distribution))
            ->add(new CapabilityDefinition('eloquent', $package, $provider, ['database'], $distribution))
            ->add(new CapabilityDefinition('mongo', $package, $provider, distribution: $distribution))
            ->add(new CapabilityDefinition('cache', $package, $provider, distribution: $distribution))
            ->add(new CapabilityDefinition('redis', $package, $provider, ['cache'], $distribution))
            ->add(new CapabilityDefinition('cqrs', $package, $provider, distribution: $distribution))
            ->add(new CapabilityDefinition('events', $package, $provider, distribution: $distribution))
            ->add(new CapabilityDefinition('docker', $package, $provider, distribution: $distribution));
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
