<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class CircularCapabilityDependencyException extends NexusException
{
    /** @param non-empty-list<string> $path */
    public static function fromPath(array $path): self
    {
        return new self('Circular capability dependency: ' . implode(' -> ', $path));
    }
}
