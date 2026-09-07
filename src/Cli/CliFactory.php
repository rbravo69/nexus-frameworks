<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Architecture\ArchitectureGuard;
use Nexus\Benchmark\BenchmarkRunner;
use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityInstaller;
use Nexus\Capability\CapabilityManifest;
use Nexus\Capability\CapabilityResolver;
use Nexus\Capability\ComposerPackageManager;
use Nexus\Capability\PackageManagerInterface;
use Nexus\Cli\Command\AboutCommand;
use Nexus\Cli\Command\AddCommand;
use Nexus\Cli\Command\ArchitectureCheckCommand;
use Nexus\Cli\Command\BenchmarkCommand;
use Nexus\Cli\Command\ConfigCommand;
use Nexus\Cli\Command\DockerCommand;
use Nexus\Cli\Command\DoctorCommand;
use Nexus\Cli\Command\MakeCommand;
use Nexus\Cli\Command\NewCommand;
use Nexus\Cli\Command\OptimizeClearCommand;
use Nexus\Cli\Command\OptimizeCommand;
use Nexus\Cli\Command\RemoveCommand;
use Nexus\Cli\Command\ServeCommand;
use Nexus\Docker\DockerComposeGenerator;

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
        $dockerGenerator = new DockerComposeGenerator();

        $commands
            ->add(new AboutCommand())
            ->add(new AddCommand($installer))
            ->add(new ArchitectureCheckCommand(new ArchitectureGuard(), $workingDirectory))
            ->add(new BenchmarkCommand(new BenchmarkRunner()))
            ->add(new ConfigCommand($workingDirectory))
            ->add(new DoctorCommand($workingDirectory));

        foreach (GeneratorType::cases() as $type) {
            $commands->add(new MakeCommand($type, $generator, $workingDirectory));
        }

        $commands
            ->add(new NewCommand(new ProjectGenerator($filesystem), $prompter, $workingDirectory))
            ->add(new OptimizeCommand($runner, $workingDirectory))
            ->add(new OptimizeClearCommand($workingDirectory))
            ->add(new RemoveCommand($installer))
            ->add(new ServeCommand($runner, $workingDirectory));

        foreach (['init', 'up', 'down', 'restart', 'status', 'logs'] as $action) {
            $commands->add(new DockerCommand($action, $workingDirectory, $runner, $dockerGenerator));
        }

        return new ConsoleApplication($commands, $output);
    }
}
