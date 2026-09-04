<?php

declare(strict_types=1);

namespace Nexus\Http;

use Nexus\Security\CsrfMiddleware;
use Nexus\Session\SessionMiddleware;

final readonly class WebMiddlewareGroup implements MiddlewareInterface
{
    public function __construct(
        private SessionMiddleware $session,
        private CsrfMiddleware $csrf,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return (new MiddlewarePipeline(
            [$this->session, $this->csrf],
            $handler,
        ))->handle($request);
    }
}
