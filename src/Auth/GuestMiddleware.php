<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthManager $auth, private string $homePath = '/') {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->auth->guest()) {
            return $handler->handle($request);
        }

        return $request->acceptsHtml()
            ? Response::redirect($this->homePath)
            : Response::json(['error' => 'Already authenticated'], 409);
    }
}
