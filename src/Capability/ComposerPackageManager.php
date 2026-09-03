<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Contracts\ProcessRunnerInterface;
use Nexus\Exception\CapabilityInstallationException;

final readonly class ComposerPackageManager implements PackageManagerInterface
{
    public function __construct(
        private ProcessRunnerInterface $runner,
        private string $workingDirectory,
    ) {
    }

    public function install(string $package): void
    {
        $exitCode = $this->runner->run(
            ['composer', 'require', $package, '--no-interaction'],
            $this->workingDirectory,
        );

        if ($exitCode !== 0) {
            throw CapabilityInstallationException::commandFailed('install', $package, $exitCode);
        }
    }

    public function remove(string $package): void
    {
        $exitCode = $this->runner->run(
            ['composer', 'remove', $package, '--no-interaction'],
            $this->workingDirectory,
        );

        if ($exitCode !== 0) {
            throw CapabilityInstallationException::commandFailed('remove', $package, $exitCode);
        }
    }
}
