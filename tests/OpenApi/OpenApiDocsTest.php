<?php

declare(strict_types=1);

namespace Nexus\Tests\OpenApi;

use Nexus\Http\Request;
use Nexus\OpenApi\OpenApiDocs;
use Nexus\Routing\Router;
use PHPUnit\Framework\TestCase;

final class OpenApiDocsTest extends TestCase
{
    public function testItRegistersSwaggerAndSchemaEndpointsWithoutDocumentingThem(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn (): never => throw new \LogicException('not called'));
        (new OpenApiDocs(title: 'Demo API', version: '1.2.3'))->register($router);

        $schemaMatch = $router->match('GET', '/openapi.json');
        $schemaHandler = $schemaMatch->route->handler();
        self::assertIsCallable($schemaHandler);
        $schemaResponse = $schemaHandler(new Request('GET', '/openapi.json'), []);
        $document = json_decode($schemaResponse->body(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('3.1.0', $document['openapi'] ?? null);
        self::assertSame('Demo API', $document['info']['title'] ?? null);
        self::assertSame('1.2.3', $document['info']['version'] ?? null);
        self::assertArrayHasKey('/users/{id}', $document['paths'] ?? []);
        self::assertArrayNotHasKey('/docs', $document['paths'] ?? []);
        self::assertArrayNotHasKey('/openapi.json', $document['paths'] ?? []);

        $docsMatch = $router->match('GET', '/docs');
        $docsHandler = $docsMatch->route->handler();
        self::assertIsCallable($docsHandler);
        $docsResponse = $docsHandler(new Request('GET', '/docs'), []);

        self::assertStringContainsString('swagger-ui', $docsResponse->body());
        self::assertStringContainsString('/openapi.json', $docsResponse->body());
    }
}
