<?php

declare(strict_types=1);

namespace Nexus\Docker;

enum DockerRuntime: string
{
    case FrankenPhp = 'frankenphp';
    case PhpFpmNginx = 'php-fpm-nginx';
    case RoadRunner = 'roadrunner';
    case OpenSwoole = 'openswoole';

    public static function parse(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new \InvalidArgumentException(sprintf('Unsupported Docker runtime "%s".', $value));
    }
}
