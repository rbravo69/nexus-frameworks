<?php

declare(strict_types=1);

namespace Nexus\Exception;

use ReflectionParameter;

final class UnresolvableDependencyException extends ContainerException
{
    public static function forParameter(ReflectionParameter $parameter, string $class): self
    {
        return new self(sprintf(
            'Cannot resolve parameter "$%s" while autowiring "%s".',
            $parameter->getName(),
            $class,
        ));
    }
}
