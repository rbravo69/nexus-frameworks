<?php

declare(strict_types=1);

namespace Nexus\Capability;

interface PackageManagerInterface
{
    public function install(string $package): void;

    public function remove(string $package): void;
}
