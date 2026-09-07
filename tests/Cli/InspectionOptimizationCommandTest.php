<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\BufferedOutput;
use Nexus\Cli\CliFactory;
use Nexus\Cli\ExitCode;
use Nexus\Tests\Support\RecordingProcessRunner;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class InspectionOptimizationCommandTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testConfigListsManifestAndReadsDotNotationKeys(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        file_put_contents($this->temporaryDirectory->path('nexus.json'), json_encode([
            'schema' => 1,
            'project' => ['name' => 'orders', 'type' => 'api'],
            'capabilities' => ['database'],
        ], JSON_THROW_ON_ERROR));
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'config', 'project.type']));
        self::assertStringContainsString('api', $output->content());
    }

    public function testOptimizeUsesAuthoritativeComposerAutoload(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        file_put_contents($this->temporaryDirectory->path('composer.json'), '{}');
        $runner = new RecordingProcessRunner();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            runner: $runner,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'optimize']));
        self::assertSame(
            ['composer', 'dump-autoload', '--classmap-authoritative', '--no-interaction'],
            $runner->command,
        );
    }

    public function testOptimizeClearRemovesOnlyNexusCacheDirectory(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $cache = $this->temporaryDirectory->path('.nexus/cache/twig');
        mkdir($cache, 0777, true);
        file_put_contents($cache . '/compiled.php', '<?php');
        file_put_contents($this->temporaryDirectory->path('keep.txt'), 'keep');
        $cli = (new CliFactory())->create(
            output: new BufferedOutput(),
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'optimize:clear']));
        self::assertDirectoryDoesNotExist($this->temporaryDirectory->path('.nexus/cache'));
        self::assertFileExists($this->temporaryDirectory->path('keep.txt'));
    }

    public function testDoctorChecksProjectFilesAndVendorAutoload(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        file_put_contents($this->temporaryDirectory->path('composer.json'), '{}');
        file_put_contents($this->temporaryDirectory->path('nexus.json'), '{}');
        mkdir($this->temporaryDirectory->path('vendor'), 0777, true);
        file_put_contents($this->temporaryDirectory->path('vendor/autoload.php'), '<?php');
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'doctor']));
        self::assertStringContainsString('[OK] composer.json present', $output->content());
        self::assertStringContainsString('[OK] nexus.json present', $output->content());
        self::assertStringContainsString('[OK] vendor/autoload.php present', $output->content());
    }
}
