<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\BufferedOutput;
use Nexus\Cli\CliFactory;
use Nexus\Cli\ExitCode;
use Nexus\Tests\Support\QueuePrompter;
use Nexus\Tests\Support\RecordingProcessRunner;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CliFactoryTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testListExposesAllPhaseThreeCommands(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        $exitCode = $cli->run(['nexus', 'list']);

        self::assertSame(ExitCode::Success, $exitCode);

        foreach (['new', 'serve', 'add', 'remove', 'make:module', 'make:controller', 'make:model', 'doctor', 'about'] as $name) {
            self::assertStringContainsString($name, $output->content());
        }
    }

    public function testNewRunsInNonInteractiveMode(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        $exitCode = $cli->run([
            'nexus',
            'new',
            'orders-api',
            '--type=api',
            '--no-interaction',
        ]);

        self::assertSame(ExitCode::Success, $exitCode);
        self::assertFileExists($this->temporaryDirectory->path('orders-api/composer.json'));
        self::assertStringContainsString('Created API REST project', $output->content());
    }

    public function testNewWizardCollectsOnlyMissingValues(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $prompter = new QueuePrompter(['catalog', 'module']);
        $cli = (new CliFactory())->create(
            output: $output,
            prompter: $prompter,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'new']));
        self::assertFileExists($this->temporaryDirectory->path('catalog/src/AppModule.php'));
    }

    public function testNonInteractiveNewRequiresAllValues(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        $exitCode = $cli->run(['nexus', 'new', 'orders', '--no-interaction']);

        self::assertSame(ExitCode::Invalid, $exitCode);
        self::assertStringContainsString('--type option is required', $output->content());
    }

    public function testServeBuildsAValidatedPhpServerCommand(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $runner = new RecordingProcessRunner();
        $cli = (new CliFactory())->create(
            output: new BufferedOutput(),
            runner: $runner,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        $exitCode = $cli->run([
            'nexus',
            'serve',
            '--host=localhost',
            '--port=8080',
            '--docroot=.',
        ]);

        self::assertSame(ExitCode::Success, $exitCode);
        self::assertSame(
            [PHP_BINARY, '-S', 'localhost:8080', '-t', $this->temporaryDirectory->path()],
            $runner->command,
        );
    }

    public function testAboutAndUnknownCommandUseStableExitCodes(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'about']));
        self::assertSame(ExitCode::Invalid, $cli->run(['nexus', 'unknown']));
        self::assertStringContainsString('Nexus Framework 0.1.0-dev', $output->content());
    }
}
