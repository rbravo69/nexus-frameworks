<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

final class EventLog
{
    /** @var list<string> */
    private array $events = [];

    public function add(string $event): void
    {
        $this->events[] = $event;
    }

    /** @return list<string> */
    public function all(): array
    {
        return $this->events;
    }
}
