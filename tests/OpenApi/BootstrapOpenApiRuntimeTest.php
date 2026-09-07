<?php

declare(strict_types=1);

namespace Nexus\Tests\OpenApi;

use Nexus\Bootstrap;
use Nexus\OpenApi\OpenApiDocs;
use Nexus\Routing\Router;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class BootstrapOpenApiRuntimeTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testApiProjectsExposeSwaggerAndOpenApiByDefault(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        mkdir($this->temporaryDirectory->path('config'), 0777, true);
        file_put_contents($this->temporaryDirectory->path('config/app.php'), <<<'PHP'
<?php
return ['name' => 'Demo API', 'type' => 'api'];
PHP
        );

        $application = Bootstrap::create($this->temporaryDirectory->path());
        $router = $application->container()->get(Router::class);

        self::assertInstanceOf(Router::class, $router);
        self::assertSame('/docs', $router->match('GET', '/docs')->route->path());
        self::assertSame('/openapi.json', $router->match('GET', '/openapi.json')->route->path());
    }

    public function testNonApiProjectsDoNotMountSwaggerAutomatically(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        mkdir($this->temporaryDirectory->path('config'), 0777, true);
        file_put_contents($this->temporaryDirectory->path('config/app.php'), <<<'PHP'
<?php
return ['name' => 'Web App', 'type' => 'monolith'];
PHP
        );

        $application = Bootstrap::create($this->temporaryDirectory->path());
        $router = $application->container()->get(Router::class);

        self::assertInstanceOf(Router::class, $router);
        self::assertSame([], $router->routes());
    }
}
