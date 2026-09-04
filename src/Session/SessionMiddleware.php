<?php

declare(strict_types=1);

namespace Nexus\Session;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\RedirectResponse;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $this->session->start();
        $this->session->ageFlashData();

        try {
            $response = $handler->handle($request->withAttribute('session', $this->session));

            if ($response instanceof RedirectResponse) {
                foreach ($response->flashData() as $key => $value) {
                    $this->session->flash($key, $value);
                }
            }

            return $response;
        } finally {
            $this->session->close();
        }
    }
}
