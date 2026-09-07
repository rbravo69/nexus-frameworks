<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;

final readonly class OptimizeClearCommand implements CommandInterface
{
    public function __construct(private string $workingDirectory)
    {
    }

    public function name(): string
    {
        return 'optimize:clear';
    }

    public function description(): string
    {
        return 'Clear Nexus application caches.';
    }

    public function usage(): string
    {
        return 'nexus optimize:clear';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $cache = rtrim($this->workingDirectory, '/\\') . DIRECTORY_SEPARATOR . '.nexus' . DIRECTORY_SEPARATOR . 'cache';

        if (!is_dir($cache)) {
            $output->writeln('No Nexus cache directory found.');

            return ExitCode::Success;
        }

        $this->removeDirectory($cache);
        $output->writeln('Cleared Nexus caches.');

        return ExitCode::Success;
    }

    private function removeDirectory(string $directory): void
    {
        $items = scandir($directory);

        if ($items === false) {
            throw new \RuntimeException(sprintf('Unable to read cache directory "%s".', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
                continue;
            }

            if (!unlink($path)) {
                throw new \RuntimeException(sprintf('Unable to remove cache file "%s".', $path));
            }
        }

        if (!rmdir($directory)) {
            throw new \RuntimeException(sprintf('Unable to remove cache directory "%s".', $directory));
        }
    }
}
