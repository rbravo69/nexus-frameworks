<?php

declare(strict_types=1);

namespace Nexus\Tests\Web;

use Nexus\Container\Container;
use Nexus\Http\HttpKernel;
use Nexus\Http\RedirectResponse;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Http\WebMiddlewareGroup;
use Nexus\Routing\Router;
use Nexus\Security\CsrfTokenManager;
use Nexus\Session\ArraySession;
use Nexus\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

use function Nexus\redirect;
use function Nexus\redirect_back;

final class WebMiddlewareRedirectTest extends TestCase
{
    public function testWebGroupAppliesTheDefaultWebMiddleware(): void
    {
        $router = new Router();
        $router->web(static function (Router $router): void {
            $router->get('/dashboard', static fn (Request $request, array $parameters): Response => Response::text('ok'));
        });

        $match = $router->match('GET', '/dashboard');

        self::assertSame([WebMiddlewareGroup::class], $match->route->middleware());
    }

    public function testWebGroupStartsSessionAndProtectsUnsafeRequestsWithCsrf(): void
    {
        $session = new ArraySession();
        $tokens = new CsrfTokenManager($session);
        $container = new Container();
        $container
            ->instance(SessionInterface::class, $session)
            ->instance(CsrfTokenManager::class, $tokens);

        $router = new Router();
        $router->web(static function (Router $router): void {
            $router->post('/profile', static fn (Request $request, array $parameters): Response => Response::text('saved'));
        });
        $kernel = new HttpKernel($router, $container);

        $blocked = $kernel->handle(new Request('POST', '/profile', ['Accept' => 'text/html']));
        self::assertSame(419, $blocked->status());

        $allowed = $kernel->handle(new Request(
            'POST',
            '/profile',
            ['X-CSRF-TOKEN' => $tokens->token()],
        ));
        self::assertSame(200, $allowed->status());
        self::assertSame('saved', $allowed->body());
    }

    public function testRedirectHelperCanFlashDataForTheNextWebRequest(): void
    {
        $session = new ArraySession();
        $tokens = new CsrfTokenManager($session);
        $container = new Container();
        $container
            ->instance(SessionInterface::class, $session)
            ->instance(CsrfTokenManager::class, $tokens);

        $router = new Router();
        $router->web(static function (Router $router): void {
            $router->post('/save', static fn (Request $request, array $parameters): RedirectResponse => redirect('/done')
                ->with('success', 'Saved'));
            $router->get('/done', static fn (Request $request, array $parameters): Response => Response::text('done'));
        });
        $kernel = new HttpKernel($router, $container);

        $response = $kernel->handle(new Request(
            'POST',
            '/save',
            ['X-CSRF-TOKEN' => $tokens->token()],
        ));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/done', $response->headers()['location'] ?? null);
        self::assertSame('Saved', $session->get('success'));

        $kernel->handle(new Request('GET', '/done'));
        self::assertSame('Saved', $session->get('success'));

        $kernel->handle(new Request('GET', '/done'));
        self::assertFalse($session->has('success'));
    }

    public function testRedirectBackUsesRefererOrFallback(): void
    {
        $request = new Request('POST', '/profile', ['Referer' => '/account']);

        self::assertSame('/account', redirect_back($request)->headers()['location'] ?? null);
        self::assertSame('/fallback', redirect_back(new Request('POST', '/profile'), '/fallback')->headers()['location'] ?? null);
    }
}
