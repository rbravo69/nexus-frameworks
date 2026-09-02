<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

final class CacheCapability extends RecordingCapability
{
    protected function name(): string
    {
        return 'cache';
    }
}
