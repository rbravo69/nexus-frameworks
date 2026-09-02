<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

final class DatabaseCapability extends RecordingCapability
{
    protected function name(): string
    {
        return 'database';
    }
}
