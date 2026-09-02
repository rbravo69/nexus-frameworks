<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Capability\PackageManagerInterface;

final class RecordingPackageManager implements PackageManagerInterface
{
    /** @var list<string> */
    public array $installed = [];

    /** @var list<string> */
    public array $removed = [];

    public function __construct(private readonly ?string $failingPackage = null)
    {
    }

    public function install(string $package): void
    {
        if ($package === $this->failingPackage) {
            throw new \RuntimeException('Simulated package installation failure.');
        }

        $this->installed[] = $package;
    }

    public function remove(string $package): void
    {
        $this->removed[] = $package;
    }
}
