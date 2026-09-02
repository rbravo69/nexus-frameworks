<?php

declare(strict_types=1);

namespace Nexus\Tests\Http;

use Nexus\Container\Container;
use Nexus\Http\HttpKernel;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;
use Nexus\Routing\Router;
use PHPUnit\Framework\TestCase;

final class HttpKernelTest extends TestCase
{
    public function testItDispatchesRoutesAndInjectsRouteParametersIntoRequest(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static function (Request $request, array $parameters): Response {
            return Response::json([
                'id' => $parameters['id'],
                'attribute' => $request->attribute('id'),
            ]);
        });

        $response = (new HttpKernel($router, new Container()))->handle(new Request('GET', '/users/99'));

        self::assertSame(200, $response->status());
        self::assertSame('{"id":"99","attribute":"99"}', $response->body());
    }

    public function testMiddlewareWrapsTheDestination(): void
    {
        $router = new Router();
        $router->add(
            'GET',
            '/secured',
            static fn (Request $request, array $parameters): Response => Response::text('ok'),
            [HeaderMiddleware::class],
        );

        $response = (new HttpKernel($router, new Container()))->handle(new Request('GET', '/secured'));

        self::assertSame('yes', $response->headers()['x-middleware'] ?? null);
    }

    public function testItRenders404And405Responses(): void
    {
        $router = new Router();
        $router->post('/bookings', static fn (Request $request, array $parameters): Response => Response::text('ok'));
        $kernel = new HttpKernel($router, new Container());

        self::assertSame(404, $kernel->handle(new Request('GET', '/missing'))->status());

        $methodNotAllowed = $kernel->handle(new Request('GET', '/bookings'));
        self::assertSame(405, $methodNotAllowed->status());
        self::assertSame('POST', $methodNotAllowed->headers()['allow'] ?? null);
    }
}

final class HeaderMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request)->withHeader('x-middleware', 'yes');
    }
}
