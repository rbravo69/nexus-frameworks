<?php

declare(strict_types=1);

namespace Nexus\Http;

use Closure;
use Nexus\Contracts\ContainerInterface;
use Nexus\Exception\MethodNotAllowedException;
use Nexus\Exception\RouteNotFoundException;
use Nexus\Routing\RouteMatch;
use Nexus\Routing\Router;
use Throwable;

final class HttpKernel implements RequestHandlerInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container,
        private readonly bool $debug = false,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request->method(), $request->path());
            $request = $request->withAttributes($match->parameters);
            $middleware = $this->resolveMiddleware($match);

            return (new MiddlewarePipeline(
                $middleware,
                new class($this, $match) implements RequestHandlerInterface {
                    public function __construct(
                        private readonly HttpKernel $kernel,
                        private readonly RouteMatch $match,
                    ) {
                    }

                    public function handle(Request $request): Response
                    {
                        return $this->kernel->dispatch($request, $this->match);
                    }
                },
            ))->handle($request);
        } catch (RouteNotFoundException $exception) {
            return Response::json(['error' => 'Not Found'], 404);
        } catch (MethodNotAllowedException $exception) {
            return Response::json(
                ['error' => 'Method Not Allowed'],
                405,
                ['allow' => implode(', ', $exception->allowedMethods())],
            );
        } catch (Throwable $exception) {
            return Response::json(
                [
                    'error' => 'Internal Server Error',
                    ...($this->debug ? ['message' => $exception->getMessage()] : []),
                ],
                500,
            );
        }
    }

    public function dispatch(Request $request, RouteMatch $match): Response
    {
        $handler = $match->route->handler();

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $this->container->make($class);
            $response = $controller->{$method}($request, $match->parameters);
        } else {
            $callable = Closure::fromCallable($handler);
            $response = $callable($request, $match->parameters);
        }

        if (!$response instanceof Response) {
            throw new \UnexpectedValueException('HTTP route handlers must return Nexus\\Http\\Response.');
        }

        return $response;
    }

    /** @return list<MiddlewareInterface> */
    private function resolveMiddleware(RouteMatch $match): array
    {
        $resolved = [];

        foreach ($match->route->middleware() as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $resolved[] = $middleware;
                continue;
            }

            $instance = $this->container->make($middleware);

            if (!$instance instanceof MiddlewareInterface) {
                throw new \UnexpectedValueException(sprintf(
                    'Middleware "%s" must implement %s.',
                    $middleware,
                    MiddlewareInterface::class,
                ));
            }

            $resolved[] = $instance;
        }

        return $resolved;
    }
}
