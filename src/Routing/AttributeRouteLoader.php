<?php

declare(strict_types=1);

namespace Nexus\Routing;

use Nexus\Routing\Attribute\Route as RouteAttribute;
use ReflectionClass;
use ReflectionMethod;

final class AttributeRouteLoader
{
    public function __construct(private readonly Router $router)
    {
    }

    /** @param class-string $controller */
    public function load(string $controller, string $prefix = ''): void
    {
        /** @var ReflectionClass<object> $reflection */
        $reflection = new ReflectionClass($controller);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(RouteAttribute::class) as $attribute) {
                $route = $attribute->newInstance();
                $path = '/' . trim($prefix . '/' . ltrim($route->path, '/'), '/');

                $this->router->add(
                    $route->methods,
                    $path,
                    [$controller, $method->getName()],
                    $route->middleware,
                    $route->name,
                );
            }
        }
    }
}
