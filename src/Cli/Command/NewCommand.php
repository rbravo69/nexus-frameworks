<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Cli\ProjectGenerator;
use Nexus\Cli\ProjectType;
use Nexus\Cli\PrompterInterface;
use Nexus\Exception\InvalidInputException;

final readonly class NewCommand implements CommandInterface
{
    public function __construct(
        private ProjectGenerator $generator,
        private PrompterInterface $prompter,
        private string $workingDirectory,
    ) {
    }

    public function name(): string
    {
        return 'new';
    }

    public function description(): string
    {
        return 'Create a new Nexus project.';
    }

    public function usage(): string
    {
        return 'nexus new <name> [--type=<type>] [--no-interaction]';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $interactive = !$input->hasOption('no-interaction');
        $name = $input->argument(0);

        if (($name === null || trim($name) === '') && $interactive) {
            $name = $this->prompter->ask('Project name');
        }

        if ($name === null || trim($name) === '') {
            throw new InvalidInputException('Project name is required in non-interactive mode.');
        }

        $typeName = $input->option('type');

        if ($typeName === null && $interactive) {
            $typeName = $this->prompter->choose(
                'What do you want to build?',
                ProjectType::choices(),
                ProjectType::Api->value,
            );
        }

        if ($typeName === null) {
            throw new InvalidInputException('The --type option is required in non-interactive mode.');
        }

        $type = ProjectType::parse($typeName);
        $target = $this->generator->generate($name, $type, $this->workingDirectory);

        $output->writeln(sprintf('Created %s project in %s', $type->label(), $target));
        $output->writeln('Next: cd ' . $name . ' && composer install && php bin/app');

        return ExitCode::Success;
    }
}
