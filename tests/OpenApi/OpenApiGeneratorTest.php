<?php

declare(strict_types=1);

namespace Nexus\Tests\OpenApi;

use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\OpenApi\Attribute\Operation;
use Nexus\OpenApi\OpenApiGenerator;
use Nexus\Routing\Router;
use PHPUnit\Framework\TestCase;

final class OpenApiGeneratorTest extends TestCase
{
    public function testItGeneratesOpenApi31FromRoutesAndAttributes(): void
    {
        $router = new Router();
        $router->add('GET', '/users/{id}', [OpenApiUserController::class, 'show'], name: 'users.show');
        $router->post('/users', static fn (Request $request, array $parameters): Response => Response::json([]));

        $document = (new OpenApiGenerator('Example API', '1.2.3'))->generate($router);

        self::assertSame('3.1.0', $document['openapi']);
        self::assertSame('Example API', $document['info']['title']);
        self::assertSame('Show a user', $document['paths']['/users/{id}']['get']['summary']);
        self::assertSame('users.show', $document['paths']['/users/{id}']['get']['operationId']);
        self::assertSame('User found', $document['paths']['/users/{id}']['get']['responses']['200']['description']);
        self::assertSame('id', $document['paths']['/users/{id}']['get']['parameters'][0]['name']);
        self::assertArrayHasKey('post', $document['paths']['/users']);
        self::assertArrayHasKey('ProblemDetails', $document['components']['schemas']);
    }
}

final class OpenApiUserController
{
    #[Operation(
        summary: 'Show a user',
        description: 'Returns one user.',
        tags: ['Users'],
        responses: [200 => 'User found', 404 => 'User not found'],
    )]
    public function show(Request $request, array $parameters): Response
    {
        return Response::json(['id' => $parameters['id']]);
    }
}
