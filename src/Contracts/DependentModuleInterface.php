<?php

declare(strict_types=1);

namespace Nexus\Contracts;

interface DependentModuleInterface extends ModuleInterface
{
    /** @return list<string> */
    public function dependencies(): array;
}
