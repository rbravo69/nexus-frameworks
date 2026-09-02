<?php

declare(strict_types=1);

namespace Nexus\Cqrs;

use Psr\Container\ContainerInterface;

final class QueryBus implements QueryBusInterface
{
    /** @var array<class-string, class-string> */
    private array $handlers = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param class-string $query
     * @param class-string $handler
     */
    public function register(string $query, string $handler): self
    {
        $this->handlers[$query] = $handler;
        return $this;
    }

    public function ask(object $query): mixed
    {
        $handlerClass = $this->handlers[$query::class] ?? throw HandlerNotFoundException::for($query);
        $handler = $this->container->get($handlerClass);

        if (!is_object($handler) || !is_callable($handler)) {
            throw new \UnexpectedValueException(sprintf('Query handler "%s" must be an invokable object.', $handlerClass));
        }

        return $handler($query);
    }

    public function handlers(): array
    {
        return $this->handlers;
    }
}
