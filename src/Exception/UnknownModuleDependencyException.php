<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class UnknownModuleDependencyException extends NexusException
{
    public static function for(string $module, string $dependency): self
    {
        return new self(sprintf(
            'Module "%s" requires unknown module "%s".',
            $module,
            $dependency,
        ));
    }
}
