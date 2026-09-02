<?php

declare(strict_types=1);

namespace Nexus\Exception;

use Nexus\Container\Scope;

final class InactiveScopeException extends ContainerException
{
    public static function for(Scope $scope, string $id): self
    {
        return new self(sprintf(
            'The "%s" scope must be active before resolving service "%s".',
            $scope->value,
            $id,
        ));
    }
}
