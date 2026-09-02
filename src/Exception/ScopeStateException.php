<?php

declare(strict_types=1);

namespace Nexus\Exception;

use Nexus\Container\Scope;

final class ScopeStateException extends ContainerException
{
    public static function notContextual(Scope $scope): self
    {
        return new self(sprintf('The "%s" scope is not contextual.', $scope->value));
    }

    public static function alreadyActive(Scope $scope): self
    {
        return new self(sprintf('The "%s" scope is already active.', $scope->value));
    }

    public static function notActive(Scope $scope): self
    {
        return new self(sprintf('The "%s" scope is not active.', $scope->value));
    }
}
