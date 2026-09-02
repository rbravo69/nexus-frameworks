<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Capability\CapabilityInstaller;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class RemoveCommand implements CommandInterface
{
    public function __construct(private CapabilityInstaller $installer)
    {
    }

    public function name(): string
    {
        return 'remove';
    }

    public function description(): string
    {
        return 'Remove an installed capability safely.';
    }

    public function usage(): string
    {
        return 'nexus remove <capability>';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $capability = $input->argument(0)
            ?? throw new InvalidInputException('A capability name is required.');
        $capability = strtolower(trim($capability));
        $removed = $this->installer->remove($capability);
        $output->writeln($removed
            ? sprintf('Removed capability: %s', $capability)
            : sprintf('Capability is not installed: %s', $capability));

        return $removed ? ExitCode::Success : ExitCode::Failure;
    }
}
