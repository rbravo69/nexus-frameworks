<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CapabilityManifest;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class AddCommand implements CommandInterface
{
    public function __construct(private CapabilityManifest $manifest)
    {
    }

    public function name(): string
    {
        return 'add';
    }

    public function description(): string
    {
        return 'Add a capability to the project manifest.';
    }

    public function usage(): string
    {
        return 'nexus add <capability>';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $capability = $input->argument(0)
            ?? throw new InvalidInputException('A capability name is required.');
        $added = $this->manifest->add($capability);
        $output->writeln($added
            ? sprintf('Added capability: %s', strtolower($capability))
            : sprintf('Capability already present: %s', strtolower($capability)));

        return ExitCode::Success;
    }
}
