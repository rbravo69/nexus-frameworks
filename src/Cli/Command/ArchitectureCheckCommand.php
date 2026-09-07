<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Architecture\ArchitectureGuard;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;

final readonly class ArchitectureCheckCommand implements CommandInterface
{
    public function __construct(
        private ArchitectureGuard $guard,
        private string $workingDirectory,
    ) {
    }

    public function name(): string
    {
        return 'architecture:check';
    }

    public function description(): string
    {
        return 'Validate module manifests and dependency boundaries.';
    }

    public function usage(): string
    {
        return 'nexus architecture:check';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $errors = $this->guard->inspect($this->workingDirectory);

        if ($errors === []) {
            $output->writeln('[OK] Architecture guard passed.');

            return ExitCode::Success;
        }

        foreach ($errors as $error) {
            $output->writeln('[FAIL] ' . $error);
        }

        return ExitCode::Failure;
    }
}
