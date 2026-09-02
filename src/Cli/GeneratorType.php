<?php

declare(strict_types=1);

namespace Nexus\Cli;

enum GeneratorType: string
{
    case Module = 'module';
    case Controller = 'controller';
    case Model = 'model';
}
