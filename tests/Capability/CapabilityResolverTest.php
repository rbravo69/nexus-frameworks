<?php

declare(strict_types=1);

namespace Nexus\Tests\Capability;

use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityDefinition;
use Nexus\Capability\CapabilityResolver;
use Nexus\Exception\CircularCapabilityDependencyException;
use Nexus\Exception\UnknownCapabilityException;
use Nexus\Tests\Support\CacheCapability;
use Nexus\Tests\Support\DatabaseCapability;
use PHPUnit\Framework\TestCase;

final class CapabilityResolverTest extends TestCase
{
    public function testItResolvesDependenciesOnceInTopologicalOrder(): void
    {
        $resolver = new CapabilityResolver($this->catalog());

        $resolved = $resolver->resolve(['cache', 'database']);

        self::assertSame(
            ['database', 'cache'],
            array_map(static fn (CapabilityDefinition $definition): string => $definition->name, $resolved),
        );
    }

    public function testItRejectsUnknownCapabilities(): void
    {
        $this->expectException(UnknownCapabilityException::class);

        (new CapabilityResolver($this->catalog()))->resolve(['missing']);
    }

    public function testItDetectsDependencyCyclesWithTheFullPath(): void
    {
        $catalog = (new CapabilityCatalog())
            ->add(new CapabilityDefinition('first', 'nexus/first', DatabaseCapability::class, ['second']))
            ->add(new CapabilityDefinition('second', 'nexus/second', CacheCapability::class, ['first']));

        $this->expectException(CircularCapabilityDependencyException::class);
        $this->expectExceptionMessage('first -> second -> first');

        (new CapabilityResolver($catalog))->resolve(['first']);
    }

    private function catalog(): CapabilityCatalog
    {
        return (new CapabilityCatalog())
            ->add(new CapabilityDefinition('database', 'nexus/database', DatabaseCapability::class))
            ->add(new CapabilityDefinition('cache', 'nexus/cache', CacheCapability::class, ['database']));
    }
}
