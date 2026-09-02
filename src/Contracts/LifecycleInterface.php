<?php

declare(strict_types=1);

namespace Nexus\Contracts;

use Nexus\Application;
use Nexus\Lifecycle\LifecycleEvent;

interface LifecycleInterface
{
    public function listen(LifecycleEvent $event, callable $listener): void;

    public function dispatch(LifecycleEvent $event, Application $application): void;
}
