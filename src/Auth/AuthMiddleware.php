<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthManager $auth,
        private string $loginPath = '/login',
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->auth->check()) {
            return $handler->handle($request->withAttribute('user', $this->auth->user()));
        }

        if ($request->acceptsHtml()) {
            return Response::redirect($this->loginPath);
        }

        return Response::json(['error' => 'Unauthenticated'], 401);
    }
}
