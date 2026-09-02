<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Application;
use Nexus\Contracts\ModuleInterface;

final class RecordingModule implements ModuleInterface
{
    public function __construct(
        private readonly string $moduleName,
        private readonly EventLog $events,
    ) {
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function register(Application $application): void
    {
        $this->events->add($this->moduleName . ':register');
    }

    public function boot(Application $application): void
    {
        $this->events->add($this->moduleName . ':boot');
    }

    public function shutdown(Application $application): void
    {
        $this->events->add($this->moduleName . ':shutdown');
    }
}
