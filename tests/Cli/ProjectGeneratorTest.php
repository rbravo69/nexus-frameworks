<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Application;
use Nexus\ApplicationState;
use Nexus\Cli\Filesystem;
use Nexus\Cli\ProjectGenerator;
use Nexus\Cli\ProjectType;
use Nexus\Exception\InvalidInputException;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class ProjectGeneratorTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItGeneratesEverySupportedProjectType(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new ProjectGenerator(new Filesystem());

        foreach (ProjectType::cases() as $type) {
            $name = 'project-' . $type->value;
            $target = $generator->generate($name, $type, $this->temporaryDirectory->path());

            self::assertFileExists($target . '/composer.json');
            self::assertFileExists($target . '/bootstrap.php');
            self::assertFileExists($target . '/src/AppModule.php');
            self::assertTrue(is_executable($target . '/bin/app'));

            $manifest = json_decode(
                (string) file_get_contents($target . '/nexus.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            self::assertIsArray($manifest);
            $project = $manifest['project'] ?? null;
            self::assertIsArray($project);
            self::assertSame($type->value, $project['type'] ?? null);
        }
    }

    public function testItRefusesToWriteIntoANonEmptyDirectory(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $target = $this->temporaryDirectory->path('existing');
        mkdir($target);
        file_put_contents($target . '/keep.txt', 'user content');

        $this->expectException(InvalidInputException::class);

        (new ProjectGenerator(new Filesystem()))->generate(
            'existing',
            ProjectType::Api,
            $this->temporaryDirectory->path(),
        );
    }

    public function testGeneratedProjectBootsTheNexusCore(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $target = (new ProjectGenerator(new Filesystem()))->generate(
            'working-app',
            ProjectType::Api,
            $this->temporaryDirectory->path(),
        );
        $vendor = dirname(__DIR__, 2) . '/vendor';

        self::assertTrue(symlink($vendor, $target . '/vendor'));

        /** @var Application $application */
        $application = require $target . '/bootstrap.php';
        $application->boot();

        self::assertSame(ApplicationState::Booted, $application->state());

        $application->shutdown();
    }
}
