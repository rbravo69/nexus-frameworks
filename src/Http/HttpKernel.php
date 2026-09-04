<?php

declare(strict_types=1);

namespace Nexus\Http;

use Nexus\Contracts\ContainerInterface;
use Nexus\Exception\MethodNotAllowedException;
use Nexus\Exception\RedirectResponseException;
use Nexus\Exception\RouteNotFoundException;
use Nexus\Routing\RouteMatch;
use Nexus\Routing\Router;
use Nexus\Security\CsrfTokenMismatchException;
use Nexus\View\View;
use Nexus\View\ViewRendererInterface;
use Nexus\View\ViewResult;
use Throwable;

final class HttpKernel implements RequestHandlerInterface
{
    /** @param list<ExceptionRendererInterface> $exceptionRenderers */
    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container,
        private readonly bool $debug = false,
        private readonly array $exceptionRenderers = [],
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
        } catch (RouteNotFoundException) {
            return $this->errorResponse($request, 404, 'Not Found');
        } catch (MethodNotAllowedException $exception) {
            return $this->errorResponse(
                $request,
                405,
                'Method Not Allowed',
                ['allow' => implode(', ', $exception->allowedMethods())],
            );
        } catch (CsrfTokenMismatchException) {
            return $this->errorResponse($request, 419, 'Page Expired');
        } catch (RedirectResponseException $exception) {
            return Response::redirect($exception->redirectTo());
        } catch (Throwable $exception) {
            foreach ($this->exceptionRenderers as $renderer) {
                if ($renderer->supports($exception)) {
                    return $renderer->render($exception, $request);
                }
            }

            return $this->errorResponse(
                $request,
                500,
                'Internal Server Error',
                [],
                $this->debug ? $exception->getMessage() : null,
            );
        }
    }

    public function dispatch(Request $request, RouteMatch $match): Response
    {
        $handler = $match->route->handler();

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $this->container->make($class);
            $callable = [$controller, $method];

            if (!is_callable($callable)) {
                throw new \UnexpectedValueException(sprintf(
                    'Controller %s::%s is not callable.',
                    $class,
                    $method,
                ));
            }

            $response = $callable($request, $match->parameters);
        } else {
            $response = $handler($request, $match->parameters);
        }

        if ($response instanceof ViewResult) {
            return $this->viewResponse($response);
        }

        if (!$response instanceof Response) {
            throw new \UnexpectedValueException(
                'HTTP route handlers must return Nexus\\Http\\Response or Nexus\\View\\ViewResult.',
            );
        }

        return $response;
    }

    /** @return list<MiddlewareInterface> */
    private function resolveMiddleware(RouteMatch $match): array
    {
        $resolved = [];

        foreach ($match->route->middleware() as $middleware) {
            $resolved[] = $this->container->make($middleware);
        }

        return $resolved;
    }

    private function viewResponse(ViewResult $result): Response
    {
        if (!$this->container->has(ViewRendererInterface::class)) {
            throw new \UnexpectedValueException('A view renderer is required to return a ViewResult.');
        }

        $views = $this->container->get(View::class);

        if (!$views instanceof View) {
            throw new \UnexpectedValueException('Nexus View runtime is not registered.');
        }

        return $views->response($result->name, $result->data, $result->status, $result->headers);
    }

    /** @param array<string, string> $headers */
    private function errorResponse(
        Request $request,
        int $status,
        string $title,
        array $headers = [],
        ?string $message = null,
    ): Response {
        if ($request->acceptsHtml() && $this->container->has(ViewRendererInterface::class)) {
            $data = [
                'status' => $status,
                'title' => $title,
                'message' => $message,
            ];

            try {
                $views = $this->container->get(View::class);

                if ($views instanceof View) {
                    try {
                        return $views->response('errors/' . $status, $data, $status, $headers);
                    } catch (Throwable) {
                        return $views->response('nexus::errors/error', $data, $status, $headers);
                    }
                }
            } catch (Throwable) {
                // Fall back to dependency-free HTML below.
            }

            $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeMessage = $message === null
                ? ''
                : '<p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';

            return Response::html(sprintf(
                '<!doctype html><html><head><meta charset="utf-8"><title>%d %s</title></head><body><main><h1>%d %s</h1>%s</main></body></html>',
                $status,
                $safeTitle,
                $status,
                $safeTitle,
                $safeMessage,
            ), $status, $headers);
        }

        return Response::json([
            'error' => $title,
            ...($message !== null ? ['message' => $message] : []),
        ], $status, $headers);
    }
}
