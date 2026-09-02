<?php

declare(strict_types=1);

namespace Nexus\Contracts;

use Nexus\Application;

interface ModuleInterface
{
    public function name(): string;

    public function register(Application $application): void;

    public function boot(Application $application): void;

    public function shutdown(Application $application): void;
}
