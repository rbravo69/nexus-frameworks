<?php

declare(strict_types=1);

namespace Nexus\Tests\Container;

use Nexus\Container\Container;
use Nexus\Container\Scope;
use Nexus\Contracts\ContainerInterface;
use Nexus\Exception\CircularDependencyException;
use Nexus\Exception\InactiveScopeException;
use Nexus\Exception\UnresolvableDependencyException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use RuntimeException;

final class ContainerTest extends TestCase
{
    public function testItAutowiresConstructorDependencies(): void
    {
        $container = new Container();

        $vehicle = $container->make(Vehicle::class);

        self::assertInstanceOf(Vehicle::class, $vehicle);
        self::assertInstanceOf(Engine::class, $vehicle->engine);
    }

    public function testItSupportsInterfaceBindings(): void
    {
        $container = new Container();
        $container->bind(Clock::class, FrozenClock::class);

        $report = $container->make(DailyReport::class);

        self::assertInstanceOf(FrozenClock::class, $report->clock);
        self::assertTrue($container->has(Clock::class));
    }

    public function testFactoriesAreLazyAndReceiveTheContainer(): void
    {
        $container = new Container();
        $counter = new FactoryCounter();
        $container->factory(
            DailyReport::class,
            static function (ContainerInterface $services) use ($counter): DailyReport {
                $counter->calls++;

                return new DailyReport($services->make(FrozenClock::class));
            },
            Scope::Singleton,
        );

        self::assertSame(0, $counter->calls);

        $first = $container->make(DailyReport::class);
        $second = $container->make(DailyReport::class);

        self::assertSame($first, $second);
        self::assertSame(1, $counter->calls);
    }

    public function testTransientAndSingletonLifetimes(): void
    {
        $container = new Container();
        $container->transient(Engine::class);
        $container->singleton(FrozenClock::class);

        self::assertNotSame($container->make(Engine::class), $container->make(Engine::class));
        self::assertSame($container->make(FrozenClock::class), $container->make(FrozenClock::class));
    }

    public function testRequestScopeReusesThenDiscardsItsInstance(): void
    {
        $container = new Container();
        $container->requestScoped(Engine::class);

        $firstRequest = $container->runInScope(
            Scope::Request,
            static function (ContainerInterface $services): Engine {
                $first = $services->make(Engine::class);

                self::assertSame($first, $services->make(Engine::class));

                return $first;
            },
        );
        $secondRequest = $container->runInScope(
            Scope::Request,
            static fn (ContainerInterface $services): Engine => $services->make(Engine::class),
        );

        self::assertNotSame($firstRequest, $secondRequest);
    }

    public function testWorkerScopeCanSpanMultipleRequestScopes(): void
    {
        $container = new Container();
        $container->workerScoped(FrozenClock::class);
        $container->requestScoped(Engine::class);

        $firstWorker = $container->runInScope(
            Scope::Worker,
            static function (ContainerInterface $services): FrozenClock {
                $clock = $services->make(FrozenClock::class);

                $services->runInScope(
                    Scope::Request,
                    static fn (ContainerInterface $request): Engine => $request->make(Engine::class),
                );
                $services->runInScope(
                    Scope::Request,
                    static fn (ContainerInterface $request): Engine => $request->make(Engine::class),
                );

                self::assertSame($clock, $services->make(FrozenClock::class));

                return $clock;
            },
        );
        $secondWorker = $container->runInScope(
            Scope::Worker,
            static fn (ContainerInterface $services): FrozenClock => $services->make(FrozenClock::class),
        );

        self::assertNotSame($firstWorker, $secondWorker);
    }

    public function testScopedServicesRequireAnActiveScope(): void
    {
        $container = new Container();
        $container->requestScoped(Engine::class);

        $this->expectException(InactiveScopeException::class);

        $container->make(Engine::class);
    }

    public function testScopeIsClosedWhenCallbackThrows(): void
    {
        $container = new Container();

        try {
            $container->runInScope(
                Scope::Request,
                static fn (ContainerInterface $services): never => throw new RuntimeException(
                    'Failure inside request for ' . $services::class . '.',
                ),
            );
        } catch (RuntimeException) {
            // The scope must still be closed by the finally block.
        }

        $container->beginScope(Scope::Request);
        $container->endScope(Scope::Request);

        self::assertFalse($container->has('missing-service'));
    }

    public function testItDetectsCircularDependencies(): void
    {
        $container = new Container();

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(CircularA::class . ' -> ' . CircularB::class . ' -> ' . CircularA::class);

        $container->make(CircularA::class);
    }

    public function testLazyReferenceDefersResolutionAndResolvesOnlyOnce(): void
    {
        $container = new Container();
        $lazy = $container->lazy(Engine::class);

        self::assertFalse($lazy->isResolved());

        $first = $lazy->value();

        self::assertTrue($lazy->isResolved());
        self::assertSame($first, $lazy->value());
    }

    public function testItUsesDefaultValuesAndRejectsRequiredScalarDependencies(): void
    {
        $container = new Container();

        self::assertSame(80, $container->make(OptionalPort::class)->port);

        $this->expectException(UnresolvableDependencyException::class);

        $container->make(RequiredPort::class);
    }

    public function testItExposesBothNexusAndPsrContainerContracts(): void
    {
        $container = new Container();

        self::assertSame($container, $container->get(ContainerInterface::class));
        self::assertSame($container, $container->get(PsrContainerInterface::class));
    }
}

final class Engine
{
}

final class Vehicle
{
    public function __construct(public readonly Engine $engine)
    {
    }
}

interface Clock
{
}

final class FrozenClock implements Clock
{
}

final class DailyReport
{
    public function __construct(public readonly Clock $clock)
    {
    }
}

final class FactoryCounter
{
    public int $calls = 0;
}

final class CircularA
{
    public function __construct(public readonly CircularB $dependency)
    {
    }
}

final class CircularB
{
    public function __construct(public readonly CircularA $dependency)
    {
    }
}

final class OptionalPort
{
    public function __construct(public readonly int $port = 80)
    {
    }
}

final class RequiredPort
{
    public function __construct(public readonly int $port)
    {
    }
}
