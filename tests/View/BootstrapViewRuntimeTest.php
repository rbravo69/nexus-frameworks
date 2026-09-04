<?php

declare(strict_types=1);

namespace Nexus\Tests\View;

use Nexus\Bootstrap;
use Nexus\Configuration\Configuration;
use Nexus\View\NativePhpRenderer;
use Nexus\View\TwigRenderer;
use Nexus\View\View;
use Nexus\View\ViewRendererInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class BootstrapViewRuntimeTest extends TestCase
{
    private ?string $directory = null;

    #[After]
    public function cleanUp(): void
    {
        if ($this->directory === null || !is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->directory);
    }

    public function testBootstrapRegistersNativePhpRendererFromFrontendConfiguration(): void
    {
        $basePath = $this->basePath();
        $viewsPath = $basePath . '/resources/views';
        mkdir($viewsPath, 0777, true);
        file_put_contents($viewsPath . '/home.php', '<h1><?= $title ?></h1>');

        $application = Bootstrap::create(
            basePath: $basePath,
            configuration: new Configuration([
                'frontend' => ['renderer' => 'php'],
            ]),
        );

        $renderer = $application->container()->get(ViewRendererInterface::class);
        $views = $application->container()->get(View::class);

        self::assertInstanceOf(NativePhpRenderer::class, $renderer);
        self::assertInstanceOf(View::class, $views);
        self::assertSame('<h1>Nexus</h1>', $views->render('home', ['title' => 'Nexus']));
    }

    public function testBootstrapRegistersTwigRendererFromFrontendConfiguration(): void
    {
        $basePath = $this->basePath();
        $viewsPath = $basePath . '/resources/views';
        mkdir($viewsPath, 0777, true);
        file_put_contents($viewsPath . '/home.twig', '<h1>{{ title }}</h1>');

        $application = Bootstrap::create(
            basePath: $basePath,
            environment: 'development',
            debug: true,
            configuration: new Configuration([
                'frontend' => ['renderer' => 'twig'],
            ]),
        );

        $renderer = $application->container()->get(ViewRendererInterface::class);
        $views = $application->container()->get(View::class);

        self::assertInstanceOf(TwigRenderer::class, $renderer);
        self::assertInstanceOf(View::class, $views);
        self::assertSame('<h1>Nexus</h1>', $views->render('home.twig', ['title' => 'Nexus']));
    }

    public function testBootstrapDoesNotRegisterViewRuntimeForClientSideFrontend(): void
    {
        $application = Bootstrap::create(
            basePath: $this->basePath(),
            configuration: new Configuration([
                'frontend' => ['renderer' => 'react'],
            ]),
        );

        self::assertFalse($application->container()->has(ViewRendererInterface::class));
        self::assertFalse($application->container()->has(View::class));
    }

    private function basePath(): string
    {
        $this->directory ??= sys_get_temp_dir() . '/nexus-bootstrap-view-' . bin2hex(random_bytes(6));

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }

        return $this->directory;
    }
}
