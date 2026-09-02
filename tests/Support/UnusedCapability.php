<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

final class UnusedCapability extends RecordingCapability
{
    protected function name(): string
    {
        return 'unused';
    }
}
