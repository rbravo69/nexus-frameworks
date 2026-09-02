<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use Nexus\Cli\ProcessRunnerInterface;

final class RecordingProcessRunner implements ProcessRunnerInterface
{
    /** @var non-empty-list<string>|null */
    public ?array $command = null;

    public ?string $workingDirectory = null;

    public function __construct(private readonly int $exitCode = 0)
    {
    }

    public function run(array $command, string $workingDirectory): int
    {
        $this->command = $command;
        $this->workingDirectory = $workingDirectory;

        return $this->exitCode;
    }
}
