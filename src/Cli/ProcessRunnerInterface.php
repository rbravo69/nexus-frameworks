<?php

declare(strict_types=1);

namespace Nexus\Cli;

interface ProcessRunnerInterface
{
    /** @param non-empty-list<string> $command */
    public function run(array $command, string $workingDirectory): int;
}
