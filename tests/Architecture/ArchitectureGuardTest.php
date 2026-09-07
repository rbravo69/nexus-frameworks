<?php

declare(strict_types=1);

namespace Nexus\Tests\Architecture;

use Nexus\Architecture\ArchitectureGuard;
use Nexus\Cli\BufferedOutput;
use Nexus\Cli\CliFactory;
use Nexus\Cli\CodeGenerator;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Filesystem;
use Nexus\Module\ModuleArchitecture;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class ArchitectureGuardTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItAcceptsAValidModuleGraph(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->module('Identity', $this->temporaryDirectory->path(), ModuleArchitecture::Hexagonal);
        $generator->module(
            'Booking',
            $this->temporaryDirectory->path(),
            ModuleArchitecture::Ddd,
            ['identity'],
        );

        self::assertSame([], (new ArchitectureGuard())->inspect($this->temporaryDirectory->path()));
    }

    public function testItDetectsUnknownDependenciesAndCycles(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->module(
            'Catalog',
            $this->temporaryDirectory->path(),
            ModuleArchitecture::Modular,
            ['booking'],
        );
        $generator->module(
            'Booking',
            $this->temporaryDirectory->path(),
            ModuleArchitecture::Modular,
            ['catalog', 'identity'],
        );

        $errors = (new ArchitectureGuard())->inspect($this->temporaryDirectory->path());

        self::assertContains('Module "booking" depends on unknown module "identity".', $errors);
        self::assertContains('Circular module dependency: booking -> catalog -> booking.', $errors);
    }

    public function testArchitectureCheckCommandUsesStableExitCodes(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->module('Identity', $this->temporaryDirectory->path());
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'architecture:check']));
        self::assertStringContainsString('[OK] Architecture guard passed.', $output->content());
    }
}
