<?php

declare(strict_types=1);

namespace Nexus\Cli;

interface PrompterInterface
{
    public function ask(string $question, ?string $default = null): string;

    /**
     * @param non-empty-array<string, string> $choices
     */
    public function choose(string $question, array $choices, ?string $default = null): string;
}
