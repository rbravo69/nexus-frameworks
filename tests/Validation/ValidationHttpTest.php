<?php

declare(strict_types=1);

namespace Nexus\Tests\Validation;

use Nexus\Container\Container;
use Nexus\Http\HttpKernel;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Rest\ValidationExceptionRenderer;
use Nexus\Routing\Router;
use Nexus\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidationHttpTest extends TestCase
{
    public function testValidationExceptionBecomesProblemDetailsResponse(): void
    {
        $router = new Router();
        $router->post('/users', static function (Request $request, array $parameters): Response {
            (new Validator())->validate(
                ['email' => 'invalid'],
                ['email' => 'required|email'],
            )->throwIfInvalid();

            return Response::json([]);
        });

        $response = (new HttpKernel(
            $router,
            new Container(),
            exceptionRenderers: [new ValidationExceptionRenderer()],
        ))->handle(new Request('POST', '/users'));

        self::assertSame(422, $response->status());
        self::assertSame('application/problem+json; charset=utf-8', $response->headers()['content-type'] ?? null);
        self::assertStringContainsString('"errors"', $response->body());
    }
}
