<?php

declare(strict_types=1);

namespace Nexus\Routing;

use Closure;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;

final class Route
{
    /**
     * @param list<string> $methods
     * @param Closure(Request, array<string, string>): mixed|array{class-string<object>, non-empty-string} $handler
     * @param list<class-string<MiddlewareInterface>> $middleware
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $path,
        private readonly Closure|array $handler,
        private readonly array $middleware = [],
        private readonly ?string $name = null,
    ) {
        if ($path === '' || !str_starts_with($path, '/')) {
            throw new \InvalidArgumentException('Route paths must start with "/".');
        }

        if ($methods === []) {
            throw new \InvalidArgumentException('A route must define at least one HTTP method.');
        }
    }

    /** @return list<string> */
    public function methods(): array
    {
        return array_values(array_unique(array_map('strtoupper', $this->methods)));
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return Closure(Request, array<string, string>): mixed|array{class-string<object>, non-empty-string} */
    public function handler(): Closure|array
    {
        return $this->handler;
    }

    /** @return list<class-string<MiddlewareInterface>> */
    public function middleware(): array
    {
        return $this->middleware;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function supportsMethod(string $method): bool
    {
        $method = strtoupper($method);
        $methods = $this->methods();

        return in_array($method, $methods, true)
            || ($method === 'HEAD' && in_array('GET', $methods, true));
    }

    public function matchesPath(string $path): bool
    {
        return preg_match($this->pattern(), $path) === 1;
    }

    /** @return array<string, string> */
    public function parameters(string $path): array
    {
        $matches = [];

        if (preg_match($this->pattern(), $path, $matches) !== 1) {
            return [];
        }

        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = rawurldecode($value);
            }
        }

        return $parameters;
    }

    private function pattern(): string
    {
        if ($this->path === '/') {
            return '#^/$#D';
        }

        $segments = explode('/', trim($this->path, '/'));
        $compiled = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $matches) === 1) {
                $compiled[] = '(?P<' . $matches[1] . '>[^/]+)';
                continue;
            }

            $compiled[] = preg_quote($segment, '#');
        }

        return '#^/' . implode('/', $compiled) . '/?$#D';
    }
}
