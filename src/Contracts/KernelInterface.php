<?php

declare(strict_types=1);

namespace Nexus\Contracts;

use Nexus\ApplicationState;

interface KernelInterface
{
    public function boot(): void;

    public function shutdown(): void;

    public function state(): ApplicationState;
}
