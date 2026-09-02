<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Application;
use Nexus\Contracts\DependentModuleInterface;

final class RecordingModule implements DependentModuleInterface
{
    /** @param list<string> $dependencies */
    public function __construct(
        private readonly string $moduleName,
        private readonly EventLog $events,
        private readonly array $dependencies = [],
    ) {
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function dependencies(): array
    {
        return $this->dependencies;
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
