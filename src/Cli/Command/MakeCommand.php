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
use Nexus\Module\ModuleArchitecture;

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
        return $this->type === GeneratorType::Module
            ? 'nexus make:module <name> [--architecture=minimal] [--depends=module-a,module-b]'
            : sprintf('nexus make:%s <name>', $this->type->value);
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $name = $input->argument(0)
            ?? throw new InvalidInputException('A generated artifact name is required.');
        $path = match ($this->type) {
            GeneratorType::Module => $this->generator->module(
                $name,
                $this->workingDirectory,
                ModuleArchitecture::parse($input->option('architecture', 'minimal') ?? 'minimal'),
                $this->dependencies($input->option('depends')),
            ),
            GeneratorType::Controller => $this->generator->controller($name, $this->workingDirectory),
            GeneratorType::Model => $this->generator->model($name, $this->workingDirectory),
        };

        $output->writeln('Created: ' . $path);

        return ExitCode::Success;
    }

    /** @return list<string> */
    private function dependencies(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $value)),
            static fn (string $dependency): bool => $dependency !== '',
        ));
    }
}
