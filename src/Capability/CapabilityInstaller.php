<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Cli\CapabilityManifest;
use Nexus\Exception\CapabilityInUseException;
use Throwable;

final readonly class CapabilityInstaller
{
    public function __construct(
        private CapabilityCatalog $catalog,
        private CapabilityResolver $resolver,
        private CapabilityManifest $manifest,
        private PackageManagerInterface $packages,
    ) {
    }

    /** @return list<string> */
    public function install(string $name): array
    {
        $installed = $this->manifest->all();
        $definitions = $this->resolver->resolve([$name]);
        $added = [];

        try {
            foreach ($definitions as $definition) {
                if (in_array($definition->name, $installed, true)) {
                    continue;
                }

                $this->packages->install($definition->package);
                $installed[] = $definition->name;
                $added[] = $definition->name;
            }

            $this->manifest->replace($installed);
        } catch (Throwable $exception) {
            foreach (array_reverse($added) as $addedName) {
                try {
                    $this->packages->remove($this->catalog->get($addedName)->package);
                } catch (Throwable) {
                }
            }

            throw $exception;
        }

        return $added;
    }

    public function remove(string $name): bool
    {
        $installed = $this->manifest->all();

        if (!in_array($name, $installed, true)) {
            return false;
        }

        $dependents = [];

        foreach ($installed as $installedName) {
            if ($installedName !== $name && $this->dependsOn($installedName, $name)) {
                $dependents[] = $installedName;
            }
        }

        if ($dependents !== []) {
            throw CapabilityInUseException::by($name, $dependents);
        }

        $definition = $this->catalog->get($name);
        $remaining = array_values(array_filter(
            $installed,
            static fn (string $installedName): bool => $installedName !== $name,
        ));

        $this->manifest->replace($remaining);

        try {
            $this->packages->remove($definition->package);
        } catch (Throwable $exception) {
            $this->manifest->replace($installed);

            throw $exception;
        }

        return true;
    }

    /** @param array<string, true> $visited */
    private function dependsOn(string $candidate, string $target, array $visited = []): bool
    {
        if (isset($visited[$candidate])) {
            return false;
        }

        $visited[$candidate] = true;

        foreach ($this->catalog->get($candidate)->dependencies as $dependency) {
            if ($dependency === $target || $this->dependsOn($dependency, $target, $visited)) {
                return true;
            }
        }

        return false;
    }
}
