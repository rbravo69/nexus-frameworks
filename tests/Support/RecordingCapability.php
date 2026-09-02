<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Application;
use Nexus\Contracts\CapabilityInterface;

abstract class RecordingCapability implements CapabilityInterface
{
    public function __construct(private readonly EventLog $events)
    {
    }

    abstract protected function name(): string;

    public function register(Application $application): void
    {
        $this->events->add($this->name() . ':register');
    }

    public function boot(Application $application): void
    {
        $this->events->add($this->name() . ':boot');
    }

    public function shutdown(Application $application): void
    {
        $this->events->add($this->name() . ':shutdown');
    }
}
