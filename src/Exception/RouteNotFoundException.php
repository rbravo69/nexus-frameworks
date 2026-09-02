<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class RouteNotFoundException extends \RuntimeException
{
    public static function for(string $method, string $path): self
    {
        return new self(sprintf('No route matched %s %s.', strtoupper($method), $path));
    }
}
