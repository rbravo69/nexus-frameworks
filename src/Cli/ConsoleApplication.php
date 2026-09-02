<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;
use Throwable;

final readonly class ConsoleApplication
{
    public function __construct(
        private CommandRegistry $commands,
        private OutputInterface $output,
    ) {
    }

    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';

        if ($name === '--help' || $name === '-h' || $name === 'list') {
            $this->renderList();

            return ExitCode::Success;
        }

        if ($name === '--version' || $name === '-V') {
            $name = 'about';
        }

        if ($name === 'help') {
            return $this->renderCommandHelp($argv[2] ?? null);
        }

        $command = $this->commands->get($name);

        if ($command === null) {
            $this->output->writeln(sprintf('Unknown command: %s', $name));
            $this->output->writeln('Run "nexus list" to see available commands.');

            return ExitCode::Invalid;
        }

        $input = new Input(array_slice($argv, 2));

        if ($input->hasOption('help')) {
            $this->renderHelp($command);

            return ExitCode::Success;
        }

        try {
            return $command->execute($input, $this->output);
        } catch (InvalidInputException $exception) {
            $this->output->writeln('Invalid input: ' . $exception->getMessage());
            $this->output->writeln('Usage: ' . $command->usage());

            return ExitCode::Invalid;
        } catch (Throwable $exception) {
            $this->output->writeln('Error: ' . $exception->getMessage());

            return ExitCode::Failure;
        }
    }

    private function renderList(): void
    {
        $this->output->writeln('Nexus Framework — Simple by default. Powerful by design.');
        $this->output->writeln();
        $this->output->writeln('Available commands:');

        foreach ($this->commands->all() as $command) {
            $this->output->writeln(sprintf('  %-18s %s', $command->name(), $command->description()));
        }
    }

    private function renderCommandHelp(?string $name): int
    {
        if ($name === null || ($command = $this->commands->get($name)) === null) {
            $this->output->writeln('Usage: nexus help <command>');

            return ExitCode::Invalid;
        }

        $this->renderHelp($command);

        return ExitCode::Success;
    }

    private function renderHelp(CommandInterface $command): void
    {
        $this->output->writeln($command->description());
        $this->output->writeln('Usage: ' . $command->usage());
    }
}
