<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class NativeProcessRunner implements ProcessRunnerInterface
{
    public function run(array $command, string $workingDirectory): int
    {
        $currentDirectory = getcwd();

        if (!chdir($workingDirectory)) {
            throw new \RuntimeException(sprintf('Unable to enter directory "%s".', $workingDirectory));
        }

        try {
            $shellCommand = implode(' ', array_map(escapeshellarg(...), $command));
            $exitCode = ExitCode::Failure;
            passthru($shellCommand, $exitCode);

            return $exitCode;
        } finally {
            if ($currentDirectory !== false) {
                chdir($currentDirectory);
            }
        }
    }
}
