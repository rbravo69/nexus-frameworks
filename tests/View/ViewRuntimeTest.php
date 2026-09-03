<?php

declare(strict_types=1);

namespace Nexus\Tests\View;

use Nexus\View\NativePhpRenderer;
use Nexus\View\TwigRenderer;
use Nexus\View\View;
use Nexus\View\ViewFinder;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class ViewRuntimeTest extends TestCase
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
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->directory);
    }

    public function testNativePhpRendererRendersDataAndHtmlResponse(): void
    {
        $views = $this->makeDirectory('views');
        file_put_contents($views . '/hello.php', '<h1><?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?></h1>');

        $view = new View(new NativePhpRenderer(new ViewFinder([$views])));
        $response = $view->response('hello', ['name' => 'Nexus']);

        self::assertSame('<h1>Nexus</h1>', $response->body());
        self::assertSame('text/html; charset=utf-8', $response->headers()['content-type'] ?? null);
    }

    public function testTwigRendererSupportsGlobalAndNamespacedModuleViews(): void
    {
        $views = $this->makeDirectory('views');
        $moduleViews = $this->makeDirectory('modules/Catalog/Views');
        file_put_contents($views . '/home.twig', '<h1>{{ title }}</h1>');
        file_put_contents($moduleViews . '/card.twig', '<article>{{ product }}</article>');

        $finder = (new ViewFinder([$views]))->addNamespace('catalog', $moduleViews);
        $renderer = new TwigRenderer($finder);

        self::assertSame('<h1>Nexus</h1>', $renderer->render('home.twig', ['title' => 'Nexus']));
        self::assertSame('<article>House</article>', $renderer->render('catalog::card.twig', ['product' => 'House']));
    }

    private function makeDirectory(string $suffix): string
    {
        $this->directory ??= sys_get_temp_dir() . '/nexus-view-' . bin2hex(random_bytes(6));
        $path = $this->directory . '/' . $suffix;
        mkdir($path, 0777, true);

        return $path;
    }
}
