<?php

declare(strict_types=1);

namespace Nexus\Http;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
