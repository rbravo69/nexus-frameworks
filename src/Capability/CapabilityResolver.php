<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Exception\CircularCapabilityDependencyException;

final readonly class CapabilityResolver
{
    public function __construct(private CapabilityCatalog $catalog)
    {
    }

    /**
     * @param list<string> $requested
     * @return list<CapabilityDefinition>
     */
    public function resolve(array $requested): array
    {
        $resolved = [];
        $visiting = [];
        $visited = [];

        foreach ($requested as $name) {
            $this->visit($name, $resolved, $visiting, $visited);
        }

        return $resolved;
    }

    /**
     * @param list<CapabilityDefinition> $resolved
     * @param list<string> $visiting
     * @param array<string, true> $visited
     */
    private function visit(string $name, array &$resolved, array &$visiting, array &$visited): void
    {
        if (isset($visited[$name])) {
            return;
        }

        $position = array_search($name, $visiting, true);

        if ($position !== false) {
            $path = array_slice($visiting, $position);
            $path[] = $name;

            throw CircularCapabilityDependencyException::fromPath($path);
        }

        $definition = $this->catalog->get($name);
        $visiting[] = $name;

        foreach ($definition->dependencies as $dependency) {
            $this->visit($dependency, $resolved, $visiting, $visited);
        }

        array_pop($visiting);
        $visited[$name] = true;
        $resolved[] = $definition;
    }
}
