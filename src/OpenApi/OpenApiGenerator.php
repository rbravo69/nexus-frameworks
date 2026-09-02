<?php

declare(strict_types=1);

namespace Nexus\OpenApi;

use Nexus\OpenApi\Attribute\Operation;
use Nexus\Routing\Route;
use Nexus\Routing\Router;
use ReflectionMethod;

final class OpenApiGenerator
{
    public function __construct(
        private readonly string $title = 'Nexus API',
        private readonly string $version = '0.1.0',
    ) {
    }

    /** @return array<string, mixed> */
    public function generate(Router $router): array
    {
        $paths = [];

        foreach ($router->routes() as $route) {
            foreach ($route->methods() as $method) {
                $paths[$route->path()][strtolower($method)] = $this->operationFor($route, $method);
            }
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->title,
                'version' => $this->version,
            ],
            'paths' => $paths,
            'components' => [
                'schemas' => [
                    'ProblemDetails' => [
                        'type' => 'object',
                        'required' => ['type', 'title', 'status'],
                        'properties' => [
                            'type' => ['type' => 'string', 'format' => 'uri-reference'],
                            'title' => ['type' => 'string'],
                            'status' => ['type' => 'integer'],
                            'detail' => ['type' => ['string', 'null']],
                            'instance' => ['type' => ['string', 'null'], 'format' => 'uri-reference'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function operationFor(Route $route, string $method): array
    {
        $attribute = $this->operationAttribute($route);
        $operationId = $attribute?->operationId ?? $route->name();
        $responses = $attribute?->responses ?? [200 => 'Successful response'];
        $document = [
            'responses' => $this->responses($responses),
        ];

        if ($operationId !== null) {
            $document['operationId'] = $operationId;
        }

        if ($attribute?->summary !== null) {
            $document['summary'] = $attribute->summary;
        }

        if ($attribute?->description !== null) {
            $document['description'] = $attribute->description;
        }

        if ($attribute !== null && $attribute->tags !== []) {
            $document['tags'] = $attribute->tags;
        }

        $parameters = $this->pathParameters($route->path());

        if ($parameters !== []) {
            $document['parameters'] = $parameters;
        }

        if ($method === 'HEAD') {
            unset($document['parameters']);
        }

        return $document;
    }

    private function operationAttribute(Route $route): ?Operation
    {
        $handler = $route->handler();

        if (!is_array($handler)) {
            return null;
        }

        [$class, $method] = $handler;
        $attributes = (new ReflectionMethod($class, $method))->getAttributes(Operation::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /** @param array<int, string> $responses @return array<string, array<string, string>> */
    private function responses(array $responses): array
    {
        $document = [];

        foreach ($responses as $status => $description) {
            $document[(string) $status] = ['description' => $description];
        }

        return $document;
    }

    /** @return list<array<string, mixed>> */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $matches);
        $parameters = [];

        foreach ($matches[1] as $name) {
            $parameters[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        return $parameters;
    }
}
