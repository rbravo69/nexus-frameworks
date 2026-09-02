<?php

declare(strict_types=1);

namespace Nexus\Cqrs;

interface QueryBusInterface
{
    public function ask(object $query): mixed;

    /** @return array<class-string, class-string> */
    public function handlers(): array;
}
