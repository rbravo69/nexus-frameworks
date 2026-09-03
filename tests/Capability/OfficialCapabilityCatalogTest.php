<?php

declare(strict_types=1);

namespace Nexus\Tests\Capability;

use Nexus\Capability\BundledCapability;
use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityDistribution;
use PHPUnit\Framework\TestCase;

final class OfficialCapabilityCatalogTest extends TestCase
{
    public function testOfficialCapabilitiesAreBundledAndResolvable(): void
    {
        $catalog = CapabilityCatalog::official();

        self::assertSame(
            ['database', 'eloquent', 'mongo', 'cache', 'redis', 'cqrs', 'events', 'docker'],
            array_keys($catalog->all()),
        );

        foreach ($catalog->all() as $definition) {
            self::assertSame('nexus/framework', $definition->package);
            self::assertSame(CapabilityDistribution::Bundled, $definition->distribution);
            self::assertSame(BundledCapability::class, $definition->provider);
            self::assertTrue(class_exists($definition->provider));
            self::assertFalse($definition->requiresComposerMutation());
        }

        self::assertSame(['database'], $catalog->get('eloquent')->dependencies);
        self::assertSame(['cache'], $catalog->get('redis')->dependencies);
    }
}
