<?php

declare(strict_types=1);

namespace Nexus\Seeder;

enum SeedScenario: string
{
    case Minimal = 'minimal';
    case Dev = 'dev';
    case Test = 'test';
    case Demo = 'demo';
    case Qa = 'qa';
    case Performance = 'performance';
    case Stress = 'stress';
    case Full = 'full';
}
