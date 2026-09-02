<?php

declare(strict_types=1);

namespace Nexus\Http;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $destination,
        private readonly int $index = 0,
    ) {
    }

    public function handle(Request $request): Response
    {
        $middleware = $this->middleware[$this->index] ?? null;

        if ($middleware === null) {
            return $this->destination->handle($request);
        }

        return $middleware->process(
            $request,
            new self($this->middleware, $this->destination, $this->index + 1),
        );
    }
}
