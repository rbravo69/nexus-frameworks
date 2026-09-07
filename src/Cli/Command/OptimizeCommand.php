<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Cli\ProcessRunnerInterface;
use Nexus\Exception\InvalidInputException;

final readonly class OptimizeCommand implements CommandInterface
{
    public function __construct(
        private ProcessRunnerInterface $runner,
        private string $workingDirectory,
    ) {
    }

    public function name(): string
    {
        return 'optimize';
    }

    public function description(): string
    {
        return 'Optimize Composer autoloading for production.';
    }

    public function usage(): string
    {
        return 'nexus optimize';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $composer = rtrim($this->workingDirectory, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composer)) {
            throw new InvalidInputException('composer.json was not found in the working directory.');
        }

        $exitCode = $this->runner->run(
            ['composer', 'dump-autoload', '--classmap-authoritative', '--no-interaction'],
            $this->workingDirectory,
        );

        if ($exitCode !== 0) {
            $output->writeln('Composer autoload optimization failed.');

            return ExitCode::Failure;
        }

        $output->writeln('Optimized Composer autoloading.');

        return ExitCode::Success;
    }
}
