<?php

declare(strict_types=1);

namespace Nexus\Rest;

use Nexus\Http\ExceptionRendererInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Validation\ValidationException;
use Throwable;

final class ValidationExceptionRenderer implements ExceptionRendererInterface
{
    public function supports(Throwable $exception): bool
    {
        return $exception instanceof ValidationException;
    }

    public function render(Throwable $exception, Request $request): Response
    {
        if (!$exception instanceof ValidationException) {
            throw new \InvalidArgumentException('ValidationExceptionRenderer only supports ValidationException.');
        }

        return ApiResponse::problem($exception->problem($request->path()));
    }
}
