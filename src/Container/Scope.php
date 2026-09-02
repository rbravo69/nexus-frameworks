<?php

declare(strict_types=1);

namespace Nexus\Container;

enum Scope: string
{
    case Singleton = 'singleton';
    case Transient = 'transient';
    case Request = 'request';
    case Worker = 'worker';

    public function isContextual(): bool
    {
        return $this === self::Request || $this === self::Worker;
    }
}
