<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthManager $auth, private string $permission) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $request->acceptsHtml()
                ? Response::redirect('/login')
                : Response::json(['error' => 'Unauthenticated'], 401);
        }

        if (!$user instanceof AuthorizableInterface || !in_array($this->permission, $user->permissions(), true)) {
            return $request->acceptsHtml()
                ? Response::html('<h1>403 Forbidden</h1>', 403)
                : Response::json(['error' => 'Forbidden'], 403);
        }

        return $handler->handle($request->withAttribute('user', $user));
    }
}
