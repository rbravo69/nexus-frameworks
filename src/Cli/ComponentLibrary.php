<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

enum ComponentLibrary: string
{
    case None = 'none';
    case DaisyUi = 'daisyui';
    case MaterialUi = 'mui';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::DaisyUi => 'DaisyUI',
            self::MaterialUi => 'Material UI',
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
                'Unknown component library "%s". Expected one of: %s.',
                $value,
                implode(', ', array_keys(self::choices())),
            ));
    }
}
