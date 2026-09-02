<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class CapabilityInstallationException extends NexusException
{
    public static function commandFailed(string $operation, string $package, int $exitCode): self
    {
        return new self(sprintf(
            'Composer could not %s package "%s" (exit code %d).',
            $operation,
            $package,
            $exitCode,
        ));
    }
}
