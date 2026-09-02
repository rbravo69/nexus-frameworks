<?php

declare(strict_types=1);

namespace Nexus\Container;

use Closure;

/** @template T */
final class Lazy
{
    private bool $resolved = false;

    /** @var T|null */
    private mixed $value = null;

    /** @param Closure(): T $resolver */
    public function __construct(private readonly Closure $resolver)
    {
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /** @return T */
    public function value(): mixed
    {
        if (!$this->resolved) {
            $this->value = ($this->resolver)();
            $this->resolved = true;
        }

        return $this->value;
    }
}
