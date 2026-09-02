<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class UnknownCapabilityException extends NexusException
{
    public static function named(string $name): self
    {
        return new self(sprintf('Unknown capability "%s".', $name));
    }
}
