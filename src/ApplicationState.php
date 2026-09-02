<?php

declare(strict_types=1);

namespace Nexus;

enum ApplicationState: string
{
    case Created = 'created';
    case Booting = 'booting';
    case Booted = 'booted';
    case ShuttingDown = 'shutting_down';
    case Terminated = 'terminated';
    case Failed = 'failed';
}
