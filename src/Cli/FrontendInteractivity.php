<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

enum FrontendInteractivity: string
{
    case None = 'none';
    case Htmx = 'htmx';
    case Alpine = 'alpine';
    case HtmxAlpine = 'htmx-alpine';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Htmx => 'HTMX',
            self::Alpine => 'Alpine.js',
            self::HtmxAlpine => 'HTMX + Alpine.js',
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
                'Unknown interactivity "%s". Expected one of: %s.',
                $value,
                implode(', ', array_keys(self::choices())),
            ));
    }
}
