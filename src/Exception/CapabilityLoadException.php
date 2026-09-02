<?php

declare(strict_types=1);

namespace Nexus\Exception;

use Nexus\Capability\CapabilityDefinition;

final class CapabilityLoadException extends NexusException
{
    public static function providerNotFound(CapabilityDefinition $definition): self
    {
        return new self(sprintf(
            'Provider "%s" for capability "%s" was not found. Install package "%s".',
            $definition->provider,
            $definition->name,
            $definition->package,
        ));
    }

    public static function invalidProvider(CapabilityDefinition $definition): self
    {
        return new self(sprintf(
            'Provider "%s" for capability "%s" must implement CapabilityInterface.',
            $definition->provider,
            $definition->name,
        ));
    }
}
