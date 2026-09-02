<?php

declare(strict_types=1);

namespace Nexus\Tests;

use Nexus\ApplicationState;
use Nexus\Bootstrap;
use Nexus\Contracts\ConfigurationInterface;
use Nexus\Contracts\KernelInterface;
use Nexus\Environment;
use Nexus\Lifecycle\LifecycleEvent;
use Nexus\Tests\Support\EventLog;
use Nexus\Tests\Support\RecordingModule;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testItBootsWithoutOptionalCapabilities(): void
    {
        $application = Bootstrap::create(__DIR__, environment: 'testing', debug: true);

        self::assertSame(ApplicationState::Created, $application->state());

        $application->boot();

        self::assertSame(ApplicationState::Booted, $application->state());
        self::assertSame('testing', $application->environment()->name);
        self::assertTrue($application->environment()->debug);

        $application->shutdown();

        self::assertSame(ApplicationState::Terminated, $application->state());
    }

    public function testLifecycleAndModulesRunInDeterministicOrder(): void
    {
        $events = new EventLog();
        $application = Bootstrap::create(__DIR__);

        foreach (LifecycleEvent::cases() as $event) {
            $application->lifecycle()->listen(
                $event,
                static function () use ($events, $event): void {
                    $events->add($event->value);
                },
            );
        }

        $application->modules()
            ->add(new RecordingModule('first', $events))
            ->add(new RecordingModule('second', $events));

        $application->boot();
        $application->shutdown();

        self::assertSame([
            'before_boot',
            'first:register',
            'second:register',
            'after_register',
            'first:boot',
            'second:boot',
            'after_boot',
            'before_shutdown',
            'second:shutdown',
            'first:shutdown',
            'after_shutdown',
        ], $events->all());
    }

    public function testBootIsIdempotent(): void
    {
        $application = Bootstrap::create(__DIR__);

        $application->boot();
        $application->boot();

        self::assertSame(ApplicationState::Booted, $application->state());
    }

    public function testBootstrapRegistersCoreServicesInTheContainer(): void
    {
        $application = Bootstrap::create(__DIR__);
        $container = $application->container();

        self::assertSame($application, $container->get(KernelInterface::class));
        self::assertSame($application->environment(), $container->get(Environment::class));
        self::assertSame($application->config(), $container->get(ConfigurationInterface::class));
    }
}
