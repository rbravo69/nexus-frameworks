<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Cli\CommandInterface;
use Nexus\Cli\ComponentLibrary;
use Nexus\Cli\CssFramework;
use Nexus\Cli\ExitCode;
use Nexus\Cli\FrontendInteractivity;
use Nexus\Cli\FrontendRenderer;
use Nexus\Cli\FrontendSelection;
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
        return 'nexus new <name> [--type=<type>] [--frontend=<frontend>] '
            . '[--interactivity=<mode>] [--css=<framework>] [--components=<library>] [--no-interaction]';
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
        $frontend = $this->frontendSelection($input, $type, $interactive);
        $target = $this->generator->generate($name, $type, $this->workingDirectory, $frontend);

        $output->writeln(sprintf('Created %s project in %s', $type->label(), $target));

        if ($frontend->renderer !== FrontendRenderer::None) {
            $output->writeln(sprintf(
                'Frontend: %s, interactivity: %s, CSS: %s, components: %s',
                $frontend->renderer->label(),
                $frontend->interactivity->label(),
                $frontend->css->label(),
                $frontend->components->label(),
            ));
        }

        $output->writeln('Next: cd ' . $name . ' && composer install && php bin/app');

        return ExitCode::Success;
    }

    private function frontendSelection(Input $input, ProjectType $type, bool $interactive): FrontendSelection
    {
        $frontendName = $input->option('frontend');
        $interactivityName = $input->option('interactivity');
        $cssName = $input->option('css');
        $componentsName = $input->option('components');

        if (!$type->supportsFrontend()) {
            if ($frontendName !== null || $interactivityName !== null || $cssName !== null || $componentsName !== null) {
                throw new InvalidInputException('Frontend options are available only for monolith project types.');
            }

            return FrontendSelection::none();
        }

        if ($frontendName === null && $interactive) {
            $frontendName = $this->prompter->choose(
                'Frontend renderer',
                FrontendRenderer::choices(),
                FrontendRenderer::Twig->value,
            );
        }

        if ($frontendName === null) {
            return FrontendSelection::none();
        }

        $renderer = FrontendRenderer::parse($frontendName);

        if ($renderer === FrontendRenderer::None) {
            return new FrontendSelection(
                $renderer,
                $interactivityName === null ? FrontendInteractivity::None : FrontendInteractivity::parse($interactivityName),
                $cssName === null ? CssFramework::None : CssFramework::parse($cssName),
                $componentsName === null ? ComponentLibrary::None : ComponentLibrary::parse($componentsName),
            );
        }

        $interactivity = FrontendInteractivity::None;

        if ($renderer->isServerRendered()) {
            if ($interactivityName === null && $interactive) {
                $interactivityName = $this->prompter->choose(
                    'Interactivity',
                    FrontendInteractivity::choices(),
                    FrontendInteractivity::None->value,
                );
            }

            if ($interactivityName !== null) {
                $interactivity = FrontendInteractivity::parse($interactivityName);
            }
        } elseif ($interactivityName !== null) {
            $interactivity = FrontendInteractivity::parse($interactivityName);
        }

        if ($cssName === null && $interactive) {
            $cssName = $this->prompter->choose(
                'CSS framework',
                CssFramework::choices(),
                CssFramework::None->value,
            );
        }

        $css = $cssName === null ? CssFramework::None : CssFramework::parse($cssName);

        if ($componentsName === null && $interactive) {
            $componentChoices = [ComponentLibrary::None->value => ComponentLibrary::None->label()];

            if ($css === CssFramework::Tailwind) {
                $componentChoices[ComponentLibrary::DaisyUi->value] = ComponentLibrary::DaisyUi->label();
            }

            if ($renderer === FrontendRenderer::React) {
                $componentChoices[ComponentLibrary::MaterialUi->value] = ComponentLibrary::MaterialUi->label();
            }

            if (count($componentChoices) > 1) {
                $componentsName = $this->prompter->choose(
                    'Component library',
                    $componentChoices,
                    ComponentLibrary::None->value,
                );
            }
        }

        return new FrontendSelection(
            $renderer,
            $interactivity,
            $css,
            $componentsName === null ? ComponentLibrary::None : ComponentLibrary::parse($componentsName),
        );
    }
}
