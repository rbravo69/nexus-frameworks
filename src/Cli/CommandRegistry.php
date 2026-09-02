<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class CommandRegistry
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function add(CommandInterface $command): self
    {
        $name = trim($command->name());

        if ($name === '') {
            throw new \InvalidArgumentException('A command name cannot be empty.');
        }

        if (isset($this->commands[$name])) {
            throw new \LogicException(sprintf('Command "%s" is already registered.', $name));
        }

        $this->commands[$name] = $command;
        ksort($this->commands);

        return $this;
    }

    public function get(string $name): ?CommandInterface
    {
        return $this->commands[$name] ?? null;
    }

    /** @return array<string, CommandInterface> */
    public function all(): array
    {
        return $this->commands;
    }
}
