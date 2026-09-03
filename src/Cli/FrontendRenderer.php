<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

enum FrontendRenderer: string
{
    case Twig = 'twig';
    case Php = 'php';
    case React = 'react';
    case Vue = 'vue';
    case Svelte = 'svelte';
    case Solid = 'solid';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Twig => 'Twig',
            self::Php => 'PHP Native',
            self::React => 'React',
            self::Vue => 'Vue.js',
            self::Svelte => 'Svelte',
            self::Solid => 'SolidJS',
            self::None => 'None',
        };
    }

    public function isServerRendered(): bool
    {
        return $this === self::Twig || $this === self::Php;
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
                'Unknown frontend "%s". Expected one of: %s.',
                $value,
                implode(', ', array_keys(self::choices())),
            ));
    }
}
