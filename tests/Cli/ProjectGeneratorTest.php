<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Application;
use Nexus\ApplicationState;
use Nexus\Cli\ComponentLibrary;
use Nexus\Cli\CssFramework;
use Nexus\Cli\Filesystem;
use Nexus\Cli\FrontendInteractivity;
use Nexus\Cli\FrontendRenderer;
use Nexus\Cli\FrontendSelection;
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

            if ($type->supportsFrontend()) {
                self::assertSame('none', $project['frontend']['renderer'] ?? null);
            }
        }
    }

    public function testItGeneratesTwigHtmxAlpineTailwindAndDaisyUi(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $target = (new ProjectGenerator(new Filesystem()))->generate(
            'twig-app',
            ProjectType::ModularMonolith,
            $this->temporaryDirectory->path(),
            new FrontendSelection(
                FrontendRenderer::Twig,
                FrontendInteractivity::HtmxAlpine,
                CssFramework::Tailwind,
                ComponentLibrary::DaisyUi,
            ),
        );

        self::assertFileExists($target . '/resources/views/home.twig');
        self::assertFileExists($target . '/resources/frontend/app.js');
        self::assertFileExists($target . '/resources/frontend/app.css');
        self::assertFileExists($target . '/vite.config.js');

        $composer = $this->jsonFile($target . '/composer.json');
        self::assertSame('^3.0', $composer['require']['twig/twig'] ?? null);

        $package = $this->jsonFile($target . '/package.json');
        self::assertSame('latest', $package['dependencies']['htmx.org'] ?? null);
        self::assertSame('latest', $package['dependencies']['alpinejs'] ?? null);
        self::assertSame('latest', $package['devDependencies']['tailwindcss'] ?? null);
        self::assertSame('latest', $package['devDependencies']['daisyui'] ?? null);

        $manifest = $this->jsonFile($target . '/nexus.json');
        self::assertSame([
            'renderer' => 'twig',
            'interactivity' => 'htmx-alpine',
            'css' => 'tailwind',
            'components' => 'daisyui',
        ], $manifest['project']['frontend'] ?? null);

        self::assertStringContainsString("@plugin \"daisyui\";", (string) file_get_contents($target . '/resources/frontend/app.css'));
    }

    public function testItGeneratesReactWithMaterialUi(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $target = (new ProjectGenerator(new Filesystem()))->generate(
            'react-app',
            ProjectType::Monolith,
            $this->temporaryDirectory->path(),
            new FrontendSelection(
                FrontendRenderer::React,
                FrontendInteractivity::None,
                CssFramework::None,
                ComponentLibrary::MaterialUi,
            ),
        );

        self::assertFileExists($target . '/resources/frontend/main.jsx');
        self::assertFileExists($target . '/vite.config.js');

        $package = $this->jsonFile($target . '/package.json');
        self::assertSame('latest', $package['dependencies']['react'] ?? null);
        self::assertSame('latest', $package['dependencies']['react-dom'] ?? null);
        self::assertSame('latest', $package['dependencies']['@mui/material'] ?? null);
        self::assertSame('latest', $package['devDependencies']['@vitejs/plugin-react'] ?? null);
    }

    public function testPhpNativeWithoutAssetsDoesNotGenerateNodeTooling(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $target = (new ProjectGenerator(new Filesystem()))->generate(
            'php-app',
            ProjectType::Monolith,
            $this->temporaryDirectory->path(),
            new FrontendSelection(FrontendRenderer::Php),
        );

        self::assertFileExists($target . '/resources/views/home.php');
        self::assertFileDoesNotExist($target . '/package.json');
        self::assertFileDoesNotExist($target . '/vite.config.js');
    }

    public function testItRejectsFrontendStacksForApiProjects(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Frontend stacks are available only for monolith project types.');

        (new ProjectGenerator(new Filesystem()))->generate(
            'api-with-frontend',
            ProjectType::Api,
            $this->temporaryDirectory->path(),
            new FrontendSelection(FrontendRenderer::React),
        );
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

    /** @return array<string, mixed> */
    private function jsonFile(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
