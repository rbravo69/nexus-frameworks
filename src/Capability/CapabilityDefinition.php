<?php

declare(strict_types=1);

namespace Nexus\Capability;

final readonly class CapabilityDefinition
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        public string $name,
        public string $package,
        public string $provider,
        public array $dependencies = [],
    ) {
        self::assertName($name);

        if (preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $package) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid Composer package "%s".', $package));
        }

        foreach ($dependencies as $dependency) {
            self::assertName($dependency);
        }

        if (count($dependencies) !== count(array_unique($dependencies))) {
            throw new \InvalidArgumentException(sprintf('Capability "%s" has duplicate dependencies.', $name));
        }

        if (in_array($name, $dependencies, true)) {
            throw new \InvalidArgumentException(sprintf('Capability "%s" cannot depend on itself.', $name));
        }
    }

    private static function assertName(string $name): void
    {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid capability name "%s".', $name));
        }
    }
}
