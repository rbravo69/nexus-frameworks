<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class DuplicateCapabilityException extends NexusException
{
    public static function named(string $name): self
    {
        return new self(sprintf('Capability "%s" is already registered.', $name));
    }
}
