<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Capability\CapabilityManifest;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CapabilityManifestTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItAddsAndRemovesCapabilitiesWithoutLosingProjectMetadata(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        file_put_contents(
            $this->temporaryDirectory->path('nexus.json'),
            json_encode([
                'schema' => 1,
                'project' => ['name' => 'shop', 'type' => 'api'],
                'capabilities' => [],
            ], JSON_THROW_ON_ERROR),
        );
        $manifest = new CapabilityManifest($this->temporaryDirectory->path());

        self::assertTrue($manifest->add('redis'));
        self::assertFalse($manifest->add('redis'));
        self::assertTrue($manifest->add('openapi'));
        self::assertSame(['openapi', 'redis'], $manifest->all());
        self::assertTrue($manifest->remove('redis'));
        self::assertFalse($manifest->remove('redis'));

        $data = json_decode(
            (string) file_get_contents($this->temporaryDirectory->path('nexus.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($data);
        $project = $data['project'] ?? null;
        self::assertIsArray($project);
        self::assertSame('shop', $project['name'] ?? null);
        self::assertSame(['openapi'], $data['capabilities']);
    }
}
