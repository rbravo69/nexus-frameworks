<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Nexus;

final class AboutCommand implements CommandInterface
{
    public function name(): string
    {
        return 'about';
    }

    public function description(): string
    {
        return 'Display Nexus and runtime information.';
    }

    public function usage(): string
    {
        return 'nexus about';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $output->writeln('Nexus Framework ' . Nexus::VERSION);
        $output->writeln('Simple by default. Powerful by design.');
        $output->writeln('PHP ' . PHP_VERSION);

        return ExitCode::Success;
    }
}
