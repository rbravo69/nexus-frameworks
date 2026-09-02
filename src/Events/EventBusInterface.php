<?php

declare(strict_types=1);

namespace Nexus\Events;

interface EventBusInterface
{
    public function dispatch(object $event): void;

    /** @return array<class-string, list<class-string>> */
    public function listeners(): array;
}
