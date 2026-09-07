<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;

final readonly class DoctorCommand implements CommandInterface
{
    public function __construct(private string $workingDirectory)
    {
    }

    public function name(): string
    {
        return 'doctor';
    }

    public function description(): string
    {
        return 'Check whether the environment and project can run Nexus.';
    }

    public function usage(): string
    {
        return 'nexus doctor';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $root = rtrim($this->workingDirectory, '/\\');
        $checks = [
            'PHP >= 8.4' => PHP_VERSION_ID >= 80400,
            'JSON extension' => extension_loaded('json'),
            'Writable working directory' => is_writable($root),
            'composer.json present' => is_file($root . DIRECTORY_SEPARATOR . 'composer.json'),
            'nexus.json present' => is_file($root . DIRECTORY_SEPARATOR . 'nexus.json'),
            'vendor/autoload.php present' => is_file($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'),
        ];
        $healthy = true;

        foreach ($checks as $label => $passed) {
            $output->writeln(sprintf('[%s] %s', $passed ? 'OK' : 'FAIL', $label));
            $healthy = $healthy && $passed;
        }

        return $healthy ? ExitCode::Success : ExitCode::Failure;
    }
}
