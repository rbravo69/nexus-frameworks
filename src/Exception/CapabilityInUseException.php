<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class CapabilityInUseException extends NexusException
{
    /** @param non-empty-list<string> $dependents */
    public static function by(string $name, array $dependents): self
    {
        return new self(sprintf(
            'Capability "%s" is required by: %s.',
            $name,
            implode(', ', $dependents),
        ));
    }
}
