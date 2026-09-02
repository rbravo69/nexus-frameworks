<?php

declare(strict_types=1);

namespace Nexus\Lifecycle;

use Closure;
use Nexus\Application;
use Nexus\Contracts\LifecycleInterface;

final class Lifecycle implements LifecycleInterface
{
    /** @var array<string, list<Closure>> */
    private array $listeners = [];

    public function listen(LifecycleEvent $event, callable $listener): void
    {
        $this->listeners[$event->value][] = Closure::fromCallable($listener);
    }

    public function dispatch(LifecycleEvent $event, Application $application): void
    {
        foreach ($this->listeners[$event->value] ?? [] as $listener) {
            $listener($application, $event);
        }
    }
}
