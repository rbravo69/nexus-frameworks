<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Cli\PrompterInterface;

final class QueuePrompter implements PrompterInterface
{
    /** @param list<string> $answers */
    public function __construct(private array $answers)
    {
    }

    public function ask(string $question, ?string $default = null): string
    {
        return array_shift($this->answers) ?? $default ?? '';
    }

    public function choose(string $question, array $choices, ?string $default = null): string
    {
        return $this->ask($question, $default);
    }
}
