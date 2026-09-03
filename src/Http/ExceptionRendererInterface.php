<?php

declare(strict_types=1);

namespace Nexus\Http;

use Throwable;

interface ExceptionRendererInterface
{
    public function supports(Throwable $exception): bool;

    public function render(Throwable $exception, Request $request): Response;
}
