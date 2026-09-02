<?php

declare(strict_types=1);

namespace Nexus\Tests\Module;

use Nexus\Bootstrap;
use Nexus\Exception\DuplicateModuleException;
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
}
