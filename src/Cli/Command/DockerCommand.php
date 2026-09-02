<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Cli\ProcessRunnerInterface;
use Nexus\Docker\DockerComposeGenerator;
use Nexus\Docker\DockerConfig;
use Nexus\Docker\DockerRuntime;
use Nexus\Docker\DockerService;

final readonly class DockerCommand implements CommandInterface
{
    public function __construct(
        private string $action,
        private string $workingDirectory,
        private ProcessRunnerInterface $runner,
        private DockerComposeGenerator $generator,
    ) {
    }

    public function name(): string
    {
        return 'docker:' . $this->action;
    }

    public function description(): string
    {
        return 'Manage optional Docker infrastructure for Nexus.';
    }

    public function usage(): string
    {
        return $this->action === 'init'
            ? 'nexus docker:init [--runtime=frankenphp] [--services=postgres,redis] [--production]'
            : 'nexus docker:' . $this->action;
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        if ($this->action === 'init') {
            $config = new DockerConfig(
                DockerRuntime::parse($input->option('runtime', 'frankenphp') ?? 'frankenphp'),
                DockerService::parseList($input->option('services', '') ?? ''),
                $input->hasOption('production'),
            );

            foreach ($this->generator->files($config) as $path => $content) {
                $target = $this->workingDirectory . '/' . $path;
                $directory = dirname($target);
                if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new \RuntimeException('Could not create Docker directory: ' . $directory);
                }
                file_put_contents($target, $content);
                $output->writeln('Created: ' . $path);
            }

            return ExitCode::Success;
        }

        $command = match ($this->action) {
            'up' => ['docker', 'compose', 'up', '-d'],
            'down' => ['docker', 'compose', 'down'],
            'restart' => ['docker', 'compose', 'restart'],
            'status' => ['docker', 'compose', 'ps'],
            'logs' => ['docker', 'compose', 'logs', '--tail=100'],
            default => throw new \LogicException('Unsupported Docker action: ' . $this->action),
        };

        return $this->runner->run($command, $this->workingDirectory);
    }
}
