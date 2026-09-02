<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CodeGenerator;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\GeneratorType;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class MakeCommand implements CommandInterface
{
    public function __construct(
        private GeneratorType $type,
        private CodeGenerator $generator,
        private string $workingDirectory,
    ) {
    }

    public function name(): string
    {
        return 'make:' . $this->type->value;
    }

    public function description(): string
    {
        return sprintf('Generate a Nexus %s.', $this->type->value);
    }

    public function usage(): string
    {
        return sprintf('nexus make:%s <name>', $this->type->value);
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $name = $input->argument(0)
            ?? throw new InvalidInputException('A generated artifact name is required.');
        $path = match ($this->type) {
            GeneratorType::Module => $this->generator->module($name, $this->workingDirectory),
            GeneratorType::Controller => $this->generator->controller($name, $this->workingDirectory),
            GeneratorType::Model => $this->generator->model($name, $this->workingDirectory),
        };

        $output->writeln('Created: ' . $path);

        return ExitCode::Success;
    }
}
