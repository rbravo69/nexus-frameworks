<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class ExitCode
{
    public const int Success = 0;
    public const int Failure = 1;
    public const int Invalid = 2;

    private function __construct()
    {
    }
}
