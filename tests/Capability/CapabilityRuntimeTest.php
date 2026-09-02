<?php

declare(strict_types=1);

namespace Nexus\Tests\Capability;

use Nexus\Bootstrap;
use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityDefinition;
use Nexus\Container\Container;
use Nexus\Tests\Support\CacheCapability;
use Nexus\Tests\Support\DatabaseCapability;
use Nexus\Tests\Support\EventLog;
use Nexus\Tests\Support\RecordingModule;
use Nexus\Tests\Support\TemporaryDirectory;
use Nexus\Tests\Support\UnusedCapability;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CapabilityRuntimeTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItLoadsOnlySelectedCapabilitiesWithDeterministicLifecycle(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        file_put_contents(
            $this->temporaryDirectory->path('nexus.json'),
            json_encode(['schema' => 1, 'capabilities' => ['cache']], JSON_THROW_ON_ERROR),
        );
        $events = new EventLog();
        $container = new Container();
        $container->instance(EventLog::class, $events);
        $catalog = (new CapabilityCatalog())
            ->add(new CapabilityDefinition('database', 'nexus/database', DatabaseCapability::class))
            ->add(new CapabilityDefinition('cache', 'nexus/cache', CacheCapability::class, ['database']))
            ->add(new CapabilityDefinition('unused', 'nexus/unused', UnusedCapability::class));

        $application = Bootstrap::create(
            $this->temporaryDirectory->path(),
            capabilityCatalog: $catalog,
            container: $container,
        );
        $application->modules()->add(new RecordingModule('app', $events));

        self::assertSame(['database', 'cache'], array_keys($application->capabilities()->all()));
        self::assertFalse($application->capabilities()->has('unused'));

        $application->boot();
        $application->shutdown();

        self::assertSame([
            'database:register',
            'cache:register',
            'app:register',
            'database:boot',
            'cache:boot',
            'app:boot',
            'app:shutdown',
            'cache:shutdown',
            'database:shutdown',
        ], $events->all());
    }
}
