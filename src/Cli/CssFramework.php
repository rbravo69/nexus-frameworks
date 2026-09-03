<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

enum CssFramework: string
{
    case None = 'none';
    case Tailwind = 'tailwind';
    case Bootstrap = 'bootstrap';
    case Bulma = 'bulma';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Tailwind => 'Tailwind CSS',
            self::Bootstrap => 'Bootstrap',
            self::Bulma => 'Bulma',
        };
    }

    /** @return non-empty-array<string, string> */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->label();
        }

        return $choices;
    }

    public static function parse(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new InvalidInputException(sprintf(
                'Unknown CSS framework "%s". Expected one of: %s.',
                $value,
                implode(', ', array_keys(self::choices())),
            ));
    }
}
