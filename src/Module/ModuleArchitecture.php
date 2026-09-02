<?php

declare(strict_types=1);

namespace Nexus\Module;

use Nexus\Exception\InvalidInputException;

enum ModuleArchitecture: string
{
    case Minimal = 'minimal';
    case Mvc = 'mvc';
    case Layered = 'layered';
    case Modular = 'modular';
    case Hexagonal = 'hexagonal';
    case Clean = 'clean';
    case Ddd = 'ddd';
    case Cqrs = 'cqrs';
    case Custom = 'custom';

    public static function parse(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new InvalidInputException(sprintf(
                'Unknown module architecture "%s". Available: %s.',
                $value,
                implode(', ', array_map(
                    static fn (self $architecture): string => $architecture->value,
                    self::cases(),
                )),
            ));
    }
}
