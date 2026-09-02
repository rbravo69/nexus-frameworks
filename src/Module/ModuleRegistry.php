<?php

declare(strict_types=1);

namespace Nexus\Module;

use Nexus\Application;
use Nexus\Contracts\ModuleInterface;
use Nexus\Exception\DuplicateModuleException;

final class ModuleRegistry
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

    private bool $locked = false;

    public function add(ModuleInterface $module): self
    {
        if ($this->locked) {
            throw new \LogicException('Modules cannot be added after registration has started.');
        }

        $name = trim($module->name());

        if ($name === '') {
            throw new \InvalidArgumentException('A module name cannot be empty.');
        }

        if (isset($this->modules[$name])) {
            throw DuplicateModuleException::named($name);
        }

        $this->modules[$name] = $module;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /** @return array<string, ModuleInterface> */
    public function all(): array
    {
        return $this->modules;
    }

    public function registerAll(Application $application): void
    {
        $this->locked = true;

        foreach ($this->modules as $module) {
            $module->register($application);
        }
    }

    public function bootAll(Application $application): void
    {
        foreach ($this->modules as $module) {
            $module->boot($application);
        }
    }

    public function shutdownAll(Application $application): void
    {
        foreach (array_reverse($this->modules, true) as $module) {
            $module->shutdown($application);
        }
    }
}
