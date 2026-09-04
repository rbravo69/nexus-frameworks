<?php

declare(strict_types=1);

namespace Nexus\Tests\Web;

use Nexus\Assets\AssetManager;
use Nexus\Http\Cookie;
use Nexus\Http\Request;
use Nexus\Session\ArraySession;
use Nexus\Session\NativeSession;
use Nexus\Session\SessionFactory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class SessionCookieViteTest extends TestCase
{
    /** @var list<string> */
    private array $directories = [];

    #[After]
    public function cleanUp(): void
    {
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }

                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($directory);
        }
    }

    public function testSessionFactorySupportsArrayNativeAndFileDrivers(): void
    {
        $factory = new SessionFactory();

        self::assertInstanceOf(ArraySession::class, $factory->create('array'));
        self::assertInstanceOf(NativeSession::class, $factory->create('native'));
        self::assertInstanceOf(NativeSession::class, $factory->create('file', ['path' => '/tmp/nexus-sessions']));
    }

    public function testCookieValueObjectSerializesSecurityAttributes(): void
    {
        $cookie = new Cookie(
            name: 'remember',
            value: 'abc:123',
            maxAge: 3600,
            secure: true,
            sameSite: 'Strict',
        );

        $header = $cookie->toHeader();

        self::assertStringContainsString('remember=abc%3A123', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('Secure', $header);
        self::assertStringContainsString('SameSite=Strict', $header);
        self::assertStringContainsString('Max-Age=3600', $header);
    }

    public function testRequestReadsCookiesWithoutManualParsing(): void
    {
        $request = new Request('GET', '/', ['Cookie' => 'theme=dark; remember=abc%3A123']);

        self::assertSame('dark', $request->cookie('theme'));
        self::assertSame('abc:123', $request->cookie('remember'));
        self::assertNull($request->cookie('missing'));
    }

    public function testViteHotFileSwitchesAssetsToDevServerAndInjectsClient(): void
    {
        $basePath = $this->temporaryDirectory();
        mkdir($basePath . '/public', 0777, true);
        file_put_contents($basePath . '/public/hot', 'http://localhost:5173');

        $assets = new AssetManager($basePath);

        self::assertTrue($assets->isHot());
        self::assertSame('http://localhost:5173/resources/frontend/app.js', $assets->url('resources/frontend/app.js'));
        self::assertSame('http://localhost:5173/@vite/client', $assets->viteClientUrl());

        $tags = $assets->tags(['resources/frontend/app.css', 'resources/frontend/app.js']);
        self::assertStringContainsString('@vite/client', $tags);
        self::assertStringContainsString('<link rel="stylesheet"', $tags);
        self::assertStringContainsString('<script type="module"', $tags);
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/nexus-web-runtime-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->directories[] = $directory;

        return $directory;
    }
}
