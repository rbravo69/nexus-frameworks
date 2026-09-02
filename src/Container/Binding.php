<?php

declare(strict_types=1);

namespace Nexus\Container;

use Closure;

final readonly class Binding
{
    public function __construct(
        public string|Closure $resolver,
        public Scope $scope,
    ) {
    }
}
