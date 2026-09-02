<?php

declare(strict_types=1);

namespace Nexus\Tests\Http;

use Nexus\Container\Container;
use Nexus\Http\HttpKernel;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Routing\Attribute\Route;
use Nexus\Routing\AttributeRouteLoader;
use Nexus\Routing\Router;
use PHPUnit\Framework\TestCase;

final class AttributeRouteLoaderTest extends TestCase
{
    public function testItLoadsControllerAttributes(): void
    {
        $router = new Router();
        (new AttributeRouteLoader($router))->load(HealthController::class, '/api');

        $response = (new HttpKernel($router, new Container()))->handle(new Request('GET', '/api/health'));

        self::assertSame(200, $response->status());
        self::assertSame('ok', $response->body());
    }
}

final class HealthController
{
    #[Route('/health', methods: ['GET'], name: 'health')]
    public function __invoke(Request $request, array $parameters): Response
    {
        return Response::text('ok');
    }
}
