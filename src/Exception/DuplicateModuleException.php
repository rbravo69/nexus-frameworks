<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class DuplicateModuleException extends NexusException
{
    public static function named(string $name): self
    {
        return new self(sprintf('A module named "%s" is already registered.', $name));
    }
}
