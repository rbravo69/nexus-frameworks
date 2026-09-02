<?php

declare(strict_types=1);

namespace Nexus\Cli;

interface CommandInterface
{
    public function name(): string;

    public function description(): string;

    public function usage(): string;

    public function execute(Input $input, OutputInterface $output): int;
}
