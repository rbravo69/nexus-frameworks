<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityInstaller;
use Nexus\Capability\CapabilityResolver;
use Nexus\Capability\ComposerPackageManager;
use Nexus\Capability\PackageManagerInterface;
use Nexus\Cli\Command\AboutCommand;
use Nexus\Cli\Command\AddCommand;
use Nexus\Cli\Command\DoctorCommand;
use Nexus\Cli\Command\MakeCommand;
use Nexus\Cli\Command\NewCommand;
use Nexus\Cli\Command\RemoveCommand;
use Nexus\Cli\Command\ServeCommand;

final class CliFactory
{
    public function create(
        ?OutputInterface $output = null,
        ?PrompterInterface $prompter = null,
        ?ProcessRunnerInterface $runner = null,
        ?string $workingDirectory = null,
        ?CapabilityCatalog $capabilityCatalog = null,
        ?PackageManagerInterface $packageManager = null,
    ): ConsoleApplication {
        $output ??= new ConsoleOutput();
        $prompter ??= new ConsolePrompter($output);
        $runner ??= new NativeProcessRunner();
        $workingDirectory ??= getcwd() ?: '.';
        $filesystem = new Filesystem();
        $generator = new CodeGenerator($filesystem);
        $manifest = new CapabilityManifest($workingDirectory);
        $capabilityCatalog ??= CapabilityCatalog::official();
        $packageManager ??= new ComposerPackageManager($runner, $workingDirectory);
        $installer = new CapabilityInstaller(
            $capabilityCatalog,
            new CapabilityResolver($capabilityCatalog),
            $manifest,
            $packageManager,
        );
        $commands = new CommandRegistry();

        $commands
            ->add(new AboutCommand())
            ->add(new AddCommand($installer))
            ->add(new DoctorCommand($workingDirectory))
            ->add(new MakeCommand(GeneratorType::Controller, $generator, $workingDirectory))
            ->add(new MakeCommand(GeneratorType::Model, $generator, $workingDirectory))
            ->add(new MakeCommand(GeneratorType::Module, $generator, $workingDirectory))
            ->add(new NewCommand(new ProjectGenerator($filesystem), $prompter, $workingDirectory))
            ->add(new RemoveCommand($installer))
            ->add(new ServeCommand($runner, $workingDirectory));

        return new ConsoleApplication($commands, $output);
    }
}
