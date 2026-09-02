<?php

declare(strict_types=1);

namespace Nexus\Cqrs;

use Psr\Container\ContainerInterface;

final class CommandBus implements CommandBusInterface
{
    /** @var array<class-string, class-string> */
    private array $handlers = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param class-string $command
     * @param class-string $handler
     */
    public function register(string $command, string $handler): self
    {
        $this->handlers[$command] = $handler;
        return $this;
    }

    public function dispatch(object $command): mixed
    {
        $handlerClass = $this->handlers[$command::class] ?? throw HandlerNotFoundException::for($command);
        $handler = $this->container->get($handlerClass);

        if (!is_object($handler) || !is_callable($handler)) {
            throw new \UnexpectedValueException(sprintf('Command handler "%s" must be an invokable object.', $handlerClass));
        }

        return $handler($command);
    }

    public function handlers(): array
    {
        return $this->handlers;
    }
}
