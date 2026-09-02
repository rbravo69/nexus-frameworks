<?php

declare(strict_types=1);

namespace Nexus\Tests\Docker;

use Nexus\Docker\DockerComposeGenerator;
use Nexus\Docker\DockerConfig;
use Nexus\Docker\DockerRuntime;
use Nexus\Docker\DockerService;
use PHPUnit\Framework\TestCase;

final class DockerComposeGeneratorTest extends TestCase
{
    public function testGeneratesOnlySelectedServices(): void
    {
        $compose = (new DockerComposeGenerator())->compose(new DockerConfig(
            DockerRuntime::FrankenPhp,
            [DockerService::Postgres, DockerService::Redis, DockerService::Mailpit],
        ));

        self::assertStringContainsString('postgres:18-alpine', $compose);
        self::assertStringContainsString('redis:8-alpine', $compose);
        self::assertStringContainsString('axllent/mailpit:latest', $compose);
        self::assertStringNotContainsString('mysql:8.4', $compose);
        self::assertStringNotContainsString('mongo:8', $compose);
    }

    public function testSupportsAllDeclaredInfrastructureServices(): void
    {
        $compose = (new DockerComposeGenerator())->compose(new DockerConfig(
            DockerRuntime::FrankenPhp,
            DockerService::cases(),
        ));

        foreach (['postgres:', 'mysql:', 'redis:', 'mongo:', 'sqlserver:', 'rabbitmq:', 'kafka:', 'mailpit:'] as $service) {
            self::assertStringContainsString($service, $compose);
        }
    }

    public function testSupportsSelectableRuntimes(): void
    {
        $generator = new DockerComposeGenerator();

        foreach (DockerRuntime::cases() as $runtime) {
            $files = $generator->files(new DockerConfig($runtime));
            self::assertArrayHasKey('docker/php/Dockerfile', $files);
            self::assertNotSame('', trim($files['docker/php/Dockerfile']));
        }
    }
}
