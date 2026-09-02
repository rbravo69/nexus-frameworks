<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class ConsoleOutput implements OutputInterface
{
    /** @var resource */
    private mixed $stream;

    /** @param resource|null $stream */
    public function __construct(mixed $stream = null)
    {
        $this->stream = $stream ?? STDOUT;

        if (!is_resource($this->stream)) {
            throw new \InvalidArgumentException('Console output requires a writable stream.');
        }
    }

    public function write(string $message): void
    {
        fwrite($this->stream, $message);
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }
}
