<?php

declare(strict_types=1);

namespace Nexus\Tests\Module;

use Nexus\Bootstrap;
use Nexus\Exception\DuplicateModuleException;
use Nexus\Exception\CircularModuleDependencyException;
use Nexus\Exception\UnknownModuleDependencyException;
use Nexus\Tests\Support\EventLog;
use Nexus\Tests\Support\RecordingModule;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function testItRejectsDuplicateModuleNames(): void
    {
        $events = new EventLog();
        $application = Bootstrap::create(__DIR__);

        $application->modules()->add(new RecordingModule('catalog', $events));

        $this->expectException(DuplicateModuleException::class);

        $application->modules()->add(new RecordingModule('catalog', $events));
    }

    public function testRegistryLocksWhenApplicationRegistrationStarts(): void
    {
        $events = new EventLog();
        $application = Bootstrap::create(__DIR__);
        $application->boot();

        $this->expectException(\LogicException::class);

        $application->modules()->add(new RecordingModule('late', $events));
    }

    public function testItRunsDependenciesBeforeDependentsRegardlessOfInsertionOrder(): void
    {
        $events = new EventLog();
        $application = Bootstrap::create(__DIR__);
        $application->modules()
            ->add(new RecordingModule('checkout', $events, ['catalog']))
            ->add(new RecordingModule('catalog', $events));

        $application->boot();
        $application->shutdown();

        self::assertSame([
            'catalog:register',
            'checkout:register',
            'catalog:boot',
            'checkout:boot',
            'checkout:shutdown',
            'catalog:shutdown',
        ], $events->all());
    }

    public function testItRejectsUnknownModuleDependenciesBeforeRegistration(): void
    {
        $application = Bootstrap::create(__DIR__);
        $application->modules()->add(new RecordingModule('checkout', new EventLog(), ['missing']));

        $this->expectException(UnknownModuleDependencyException::class);
        $this->expectExceptionMessage('checkout');

        $application->boot();
    }

    public function testItDetectsModuleDependencyCyclesWithTheirPath(): void
    {
        $events = new EventLog();
        $application = Bootstrap::create(__DIR__);
        $application->modules()
            ->add(new RecordingModule('catalog', $events, ['checkout']))
            ->add(new RecordingModule('checkout', $events, ['catalog']));

        $this->expectException(CircularModuleDependencyException::class);
        $this->expectExceptionMessage('catalog -> checkout -> catalog');

        $application->boot();
    }
}
