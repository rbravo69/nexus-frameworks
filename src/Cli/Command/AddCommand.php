<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Capability\CapabilityInstaller;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class AddCommand implements CommandInterface
{
    public function __construct(private CapabilityInstaller $installer)
    {
    }

    public function name(): string
    {
        return 'add';
    }

    public function description(): string
    {
        return 'Install a capability and its dependencies.';
    }

    public function usage(): string
    {
        return 'nexus add <capability>';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $capability = $input->argument(0)
            ?? throw new InvalidInputException('A capability name is required.');
        $capability = strtolower(trim($capability));
        $added = $this->installer->install($capability);

        if ($added === []) {
            $output->writeln(sprintf('Capability already installed: %s', $capability));

            return ExitCode::Success;
        }

        foreach ($added as $name) {
            $output->writeln(sprintf('Installed capability: %s', $name));
        }

        return ExitCode::Success;
    }
}
