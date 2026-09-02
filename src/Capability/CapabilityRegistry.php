<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Application;
use Nexus\Contracts\CapabilityInterface;
use Nexus\Exception\DuplicateCapabilityException;

final class CapabilityRegistry
{
    /** @var array<string, CapabilityInterface> */
    private array $capabilities = [];

    private bool $locked = false;

    public function add(string $name, CapabilityInterface $capability): self
    {
        if ($this->locked) {
            throw new \LogicException('Capabilities cannot be added after registration has started.');
        }

        if (isset($this->capabilities[$name])) {
            throw DuplicateCapabilityException::named($name);
        }

        $this->capabilities[$name] = $capability;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->capabilities[$name]);
    }

    public function get(string $name): ?CapabilityInterface
    {
        return $this->capabilities[$name] ?? null;
    }

    /** @return array<string, CapabilityInterface> */
    public function all(): array
    {
        return $this->capabilities;
    }

    public function registerAll(Application $application): void
    {
        $this->locked = true;

        foreach ($this->capabilities as $capability) {
            $capability->register($application);
        }
    }

    public function bootAll(Application $application): void
    {
        foreach ($this->capabilities as $capability) {
            $capability->boot($application);
        }
    }

    public function shutdownAll(Application $application): void
    {
        foreach (array_reverse($this->capabilities, true) as $capability) {
            $capability->shutdown($application);
        }
    }
}
