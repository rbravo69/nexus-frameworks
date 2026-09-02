<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class BufferedOutput implements OutputInterface
{
    private string $buffer = '';

    public function write(string $message): void
    {
        $this->buffer .= $message;
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function content(): string
    {
        return $this->buffer;
    }
}
