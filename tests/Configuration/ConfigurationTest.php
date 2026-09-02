<?php

declare(strict_types=1);

namespace Nexus\Tests\Configuration;

use Nexus\Configuration\Configuration;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function testItReadsNestedValuesUsingDotNotation(): void
    {
        $configuration = new Configuration([
            'app' => [
                'name' => 'Nexus',
                'features' => ['http' => false],
                'nullable' => null,
            ],
        ]);

        self::assertSame('Nexus', $configuration->get('app.name'));
        self::assertFalse($configuration->get('app.features.http'));
        self::assertNull($configuration->get('app.nullable'));
        self::assertTrue($configuration->has('app.nullable'));
        self::assertSame('fallback', $configuration->get('app.missing', 'fallback'));
    }
}
