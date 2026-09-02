<?php

declare(strict_types=1);

namespace Nexus\Routing;

use Closure;
use Nexus\Exception\MethodNotAllowedException;
use Nexus\Exception\RouteNotFoundException;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var list<array{prefix: string, middleware: list<class-string<MiddlewareInterface>>}> */
    private array $groups = [];

    /**
     * @param string|list<string> $methods
     * @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler
     * @param list<class-string<MiddlewareInterface>> $middleware
     */
    public function add(
        string|array $methods,
        string $path,
        mixed $handler,
        array $middleware = [],
        ?string $name = null,
    ): Route {
        $methodList = is_string($methods) ? [$methods] : $methods;
        $normalizedHandler = $this->normalizeHandler($handler);
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groups as $group) {
            $prefix .= $group['prefix'];
            $groupMiddleware = [...$groupMiddleware, ...$group['middleware']];
        }

        $fullPath = $this->normalizePath($prefix . '/' . ltrim($path, '/'));
        $route = new Route(
            $methodList,
            $fullPath,
            $normalizedHandler,
            [...$groupMiddleware, ...$middleware],
            $name,
        );
        $this->routes[] = $route;

        return $route;
    }

    /** @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler */
    public function get(string $path, mixed $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    /** @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler */
    public function post(string $path, mixed $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    /** @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler */
    public function put(string $path, mixed $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }

    /** @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler */
    public function patch(string $path, mixed $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }

    /** @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler */
    public function delete(string $path, mixed $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * @param callable(self): void $routes
     * @param list<class-string<MiddlewareInterface>> $middleware
     */
    public function group(string $prefix, callable $routes, array $middleware = []): void
    {
        $this->groups[] = [
            'prefix' => $this->normalizePrefix($prefix),
            'middleware' => $middleware,
        ];

        try {
            $routes($this);
        } finally {
            array_pop($this->groups);
        }
    }

    public function match(string $method, string $path): RouteMatch
    {
        $allowed = [];
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            if (!$route->matchesPath($normalizedPath)) {
                continue;
            }

            if ($route->supportsMethod($method)) {
                return new RouteMatch($route, $route->parameters($normalizedPath));
            }

            $allowed = [...$allowed, ...$route->methods()];
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException(
                $method,
                $normalizedPath,
                array_values(array_unique($allowed)),
            );
        }

        throw RouteNotFoundException::for($method, $normalizedPath);
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @param (callable(Request, array<string, string>): Response)|array{class-string<object>, non-empty-string} $handler
     * @return Closure(Request, array<string, string>): Response|array{class-string<object>, non-empty-string}
     */
    private function normalizeHandler(mixed $handler): Closure|array
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;

            if (is_object($class)) {
                throw new \InvalidArgumentException('Controller handlers must use a class-string, not an object instance.');
            }

            /** @var class-string<object> $class */
            return [$class, $method];
        }

        return Closure::fromCallable($handler);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, '/');

        return $prefix === '' ? '' : '/' . $prefix;
    }
}
