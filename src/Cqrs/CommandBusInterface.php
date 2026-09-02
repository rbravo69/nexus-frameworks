<?php

declare(strict_types=1);

namespace Nexus\Cqrs;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;

    /** @return array<class-string, class-string> */
    public function handlers(): array;
}
