<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Cli\ProcessRunnerInterface;
use Nexus\Exception\InvalidInputException;

final readonly class ServeCommand implements CommandInterface
{
    public function __construct(
        private ProcessRunnerInterface $runner,
        private string $workingDirectory,
    ) {
    }

    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Start the PHP development server.';
    }

    public function usage(): string
    {
        return 'nexus serve [--host=127.0.0.1] [--port=8000] [--docroot=public]';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $host = $input->option('host', '127.0.0.1') ?? '127.0.0.1';
        $portValue = $input->option('port', '8000') ?? '8000';

        if (preg_match('/^[A-Za-z0-9.:-]+$/', $host) !== 1) {
            throw new InvalidInputException('The server host is invalid.');
        }

        if (!ctype_digit($portValue)) {
            throw new InvalidInputException('The server port must be between 1 and 65535.');
        }

        $port = (int) $portValue;

        if ($port < 1 || $port > 65535) {
            throw new InvalidInputException('The server port must be between 1 and 65535.');
        }

        $defaultDocroot = is_dir($this->workingDirectory . '/public') ? 'public' : '.';
        $docrootOption = $input->option('docroot', $defaultDocroot) ?? $defaultDocroot;
        $docroot = $this->workingDirectory . DIRECTORY_SEPARATOR . trim($docrootOption, '/\\');
        $resolvedDocroot = realpath($docroot);

        if ($resolvedDocroot === false || !is_dir($resolvedDocroot)) {
            throw new InvalidInputException(sprintf('Document root "%s" does not exist.', $docrootOption));
        }

        $output->writeln(sprintf('Nexus development server: http://%s:%d', $host, $port));

        return $this->runner->run(
            [PHP_BINARY, '-S', sprintf('%s:%d', $host, $port), '-t', $resolvedDocroot],
            $this->workingDirectory,
        );
    }
}
