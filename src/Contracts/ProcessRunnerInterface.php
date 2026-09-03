<?php

declare(strict_types=1);

namespace Nexus\Contracts;

interface ProcessRunnerInterface
{
    /** @param list<string> $command */
    public function run(array $command, string $workingDirectory): int;
}
