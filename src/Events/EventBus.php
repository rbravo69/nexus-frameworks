<?php

declare(strict_types=1);

namespace Nexus\Events;

use Psr\Container\ContainerInterface;

final class EventBus implements EventBusInterface
{
    /** @var array<class-string, list<class-string>> */
    private array $listeners = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param class-string $event
     * @param class-string $listener
     */
    public function listen(string $event, string $listener): self
    {
        $this->listeners[$event] ??= [];
        $this->listeners[$event][] = $listener;
        return $this;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $listenerClass) {
            $listener = $this->container->get($listenerClass);

            if (!is_object($listener) || !is_callable($listener)) {
                throw new \UnexpectedValueException(sprintf('Event listener "%s" must be an invokable object.', $listenerClass));
            }

            $listener($event);
        }
    }

    public function listeners(): array
    {
        return $this->listeners;
    }
}
