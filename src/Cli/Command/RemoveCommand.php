<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CapabilityManifest;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class RemoveCommand implements CommandInterface
{
    public function __construct(private CapabilityManifest $manifest)
    {
    }

    public function name(): string
    {
        return 'remove';
    }

    public function description(): string
    {
        return 'Remove a capability from the project manifest.';
    }

    public function usage(): string
    {
        return 'nexus remove <capability>';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $capability = $input->argument(0)
            ?? throw new InvalidInputException('A capability name is required.');
        $removed = $this->manifest->remove($capability);
        $output->writeln($removed
            ? sprintf('Removed capability: %s', strtolower($capability))
            : sprintf('Capability is not installed: %s', strtolower($capability)));

        return $removed ? ExitCode::Success : ExitCode::Failure;
    }
}
