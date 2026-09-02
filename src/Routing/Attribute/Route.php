<?php

declare(strict_types=1);

namespace Nexus\Routing\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Route
{
    /**
     * @param list<string> $methods
     * @param list<class-string<\Nexus\Http\MiddlewareInterface>> $middleware
     */
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public array $middleware = [],
        public ?string $name = null,
    ) {
    }
}
