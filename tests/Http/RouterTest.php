<?php

declare(strict_types=1);

namespace Nexus\Tests\Http;

use Nexus\Exception\MethodNotAllowedException;
use Nexus\Exception\RouteNotFoundException;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testItMatchesStaticAndParameterizedRoutes(): void
    {
        $router = new Router();
        $router->get('/health', static fn (Request $request, array $parameters): Response => Response::text('ok'));
        $router->get('/users/{id}', static fn (Request $request, array $parameters): Response => Response::text('user'));

        self::assertSame([], $router->match('GET', '/health')->parameters);
        self::assertSame(['id' => '42'], $router->match('GET', '/users/42')->parameters);
    }

    public function testHeadFallsBackToGet(): void
    {
        $router = new Router();
        $router->get('/health', static fn (Request $request, array $parameters): Response => Response::text('ok'));

        self::assertSame('/health', $router->match('HEAD', '/health')->route->path());
    }

    public function testItDistinguishesMethodNotAllowedFromMissingRoute(): void
    {
        $router = new Router();
        $router->post('/bookings', static fn (Request $request, array $parameters): Response => Response::text('created'));

        try {
            $router->match('GET', '/bookings');
            self::fail('Expected method-not-allowed exception.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['POST'], $exception->allowedMethods());
        }

        $this->expectException(RouteNotFoundException::class);
        $router->match('GET', '/missing');
    }

    public function testGroupsApplyPrefixes(): void
    {
        $router = new Router();
        $router->group('/api/v1', static function (Router $router): void {
            $router->get('/users/{id}', static fn (Request $request, array $parameters): Response => Response::text('user'));
        });

        self::assertSame(['id' => '7'], $router->match('GET', '/api/v1/users/7')->parameters);
    }
}
