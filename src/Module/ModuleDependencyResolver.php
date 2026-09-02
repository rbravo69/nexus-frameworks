<?php

declare(strict_types=1);

namespace Nexus\Module;

use Nexus\Contracts\DependentModuleInterface;
use Nexus\Contracts\ModuleInterface;
use Nexus\Exception\CircularModuleDependencyException;
use Nexus\Exception\UnknownModuleDependencyException;

final class ModuleDependencyResolver
{
    /**
     * @param array<string, ModuleInterface> $modules
     * @return array<string, ModuleInterface>
     */
    public function resolve(array $modules): array
    {
        $resolved = [];
        $visiting = [];
        $visited = [];

        foreach (array_keys($modules) as $name) {
            $this->visit($name, $modules, $resolved, $visiting, $visited);
        }

        return $resolved;
    }

    /**
     * @param array<string, ModuleInterface> $modules
     * @param array<string, ModuleInterface> $resolved
     * @param list<string> $visiting
     * @param array<string, true> $visited
     */
    private function visit(
        string $name,
        array $modules,
        array &$resolved,
        array &$visiting,
        array &$visited,
    ): void {
        if (isset($visited[$name])) {
            return;
        }

        $position = array_search($name, $visiting, true);

        if ($position !== false) {
            $path = array_slice($visiting, $position);
            $path[] = $name;

            throw CircularModuleDependencyException::fromPath($path);
        }

        $module = $modules[$name];
        $visiting[] = $name;
        $dependencies = $module instanceof DependentModuleInterface
            ? $module->dependencies()
            : [];

        foreach ($dependencies as $dependency) {
            if (!isset($modules[$dependency])) {
                throw UnknownModuleDependencyException::for($name, $dependency);
            }

            $this->visit($dependency, $modules, $resolved, $visiting, $visited);
        }

        array_pop($visiting);
        $visited[$name] = true;
        $resolved[$name] = $module;
    }
}
