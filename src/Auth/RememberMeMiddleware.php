<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class RememberMeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthManager $auth,
        private RememberMeManager $remember,
        private RememberCookie $cookie,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->auth->guest()) {
            $token = $this->cookie->tokenFrom($request);

            if ($token !== null) {
                $user = $this->remember->recall($token);

                if ($user !== null) {
                    $this->auth->login($user);
                    $request = $request->withAttribute('user', $user);
                }
            }
        }

        return $handler->handle($request);
    }
}
