<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Application;
use Nexus\Contracts\CapabilityInterface;

final class BundledCapability implements CapabilityInterface
{
    public function register(Application $application): void
    {
    }

    public function boot(Application $application): void
    {
    }

    public function shutdown(Application $application): void
    {
    }
}
