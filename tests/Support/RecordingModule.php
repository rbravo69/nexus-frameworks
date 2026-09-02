<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Application;
use Nexus\Contracts\ModuleInterface;

final class RecordingModule implements ModuleInterface
{
    /** @var list<string> */
    private array $events;

    /** @param list<string> $events */
    public function __construct(
        private readonly string $moduleName,
        array &$events,
    ) {
        $this->events = &$events;
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function register(Application $application): void
    {
        $this->events[] = $this->moduleName . ':register';
    }

    public function boot(Application $application): void
    {
        $this->events[] = $this->moduleName . ':boot';
    }

    public function shutdown(Application $application): void
    {
        $this->events[] = $this->moduleName . ':shutdown';
    }
}
