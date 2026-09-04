<?php

declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    public function __construct(private CsrfTokenManager $tokens)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $token = $request->header('X-CSRF-TOKEN');

        if ($token === null) {
            $input = $request->input('_token');
            $token = is_string($input) ? $input : null;
        }

        if (!$this->tokens->verify($token)) {
            throw new CsrfTokenMismatchException();
        }

        return $handler->handle($request);
    }
}
