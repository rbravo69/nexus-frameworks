<?php

declare(strict_types=1);

namespace Nexus\Capability;

enum CapabilityDistribution: string
{
    case Bundled = 'bundled';
    case Composer = 'composer';
}
