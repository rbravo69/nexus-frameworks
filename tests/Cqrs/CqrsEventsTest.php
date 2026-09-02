<?php

declare(strict_types=1);

namespace Nexus\Tests\Cqrs;

use Nexus\Container\Container;
use Nexus\Cqrs\CommandBus;
use Nexus\Cqrs\HandlerNotFoundException;
use Nexus\Cqrs\QueryBus;
use Nexus\Events\EventBus;
use PHPUnit\Framework\TestCase;

final class CqrsEventsTest extends TestCase
{
    public function testCommandBusResolvesInvokableHandlerFromContainer(): void
    {
        $bus = (new CommandBus(new Container()))->register(CreateUser::class, CreateUserHandler::class);

        self::assertSame('created:Rafael', $bus->dispatch(new CreateUser('Rafael')));
        self::assertSame([CreateUser::class => CreateUserHandler::class], $bus->handlers());
    }

    public function testQueryBusReturnsHandlerResult(): void
    {
        $bus = (new QueryBus(new Container()))->register(FindUser::class, FindUserHandler::class);

        self::assertSame(['id' => 7, 'name' => 'Rafael'], $bus->ask(new FindUser(7)));
    }

    public function testMissingHandlerFailsExplicitly(): void
    {
        $this->expectException(HandlerNotFoundException::class);
        (new CommandBus(new Container()))->dispatch(new CreateUser('Rafael'));
    }

    public function testEventBusDispatchesAllListenersInRegistrationOrder(): void
    {
        $collector = new EventCollector();
        $container = new Container();
        $container->instance(EventCollector::class, $collector);

        $bus = (new EventBus($container))
            ->listen(UserCreated::class, AuditUserCreated::class)
            ->listen(UserCreated::class, WelcomeUserCreated::class);

        $bus->dispatch(new UserCreated('Rafael'));

        self::assertSame(['audit:Rafael', 'welcome:Rafael'], $collector->events);
        self::assertSame([
            UserCreated::class => [AuditUserCreated::class, WelcomeUserCreated::class],
        ], $bus->listeners());
    }
}

final readonly class CreateUser
{
    public function __construct(public string $name)
    {
    }
}

final class CreateUserHandler
{
    public function __invoke(CreateUser $command): string
    {
        return 'created:' . $command->name;
    }
}

final readonly class FindUser
{
    public function __construct(public int $id)
    {
    }
}

final class FindUserHandler
{
    /** @return array{id: int, name: string} */
    public function __invoke(FindUser $query): array
    {
        return ['id' => $query->id, 'name' => 'Rafael'];
    }
}

final readonly class UserCreated
{
    public function __construct(public string $name)
    {
    }
}

final class EventCollector
{
    /** @var list<string> */
    public array $events = [];
}

final readonly class AuditUserCreated
{
    public function __construct(private EventCollector $collector)
    {
    }

    public function __invoke(UserCreated $event): void
    {
        $this->collector->events[] = 'audit:' . $event->name;
    }
}

final readonly class WelcomeUserCreated
{
    public function __construct(private EventCollector $collector)
    {
    }

    public function __invoke(UserCreated $event): void
    {
        $this->collector->events[] = 'welcome:' . $event->name;
    }
}
