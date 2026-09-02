<?php

declare(strict_types=1);

namespace Nexus\Contracts;

use Nexus\Module\ModuleArchitecture;

interface ArchitecturalModuleInterface extends DependentModuleInterface
{
    public function architecture(): ModuleArchitecture;
}
