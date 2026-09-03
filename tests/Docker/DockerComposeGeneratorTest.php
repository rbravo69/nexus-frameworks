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

    public function testEachRuntimeGeneratesItsRealRuntimeArtifacts(): void
    {
        $generator = new DockerComposeGenerator();

        $franken = $generator->files(new DockerConfig(DockerRuntime::FrankenPhp));
        self::assertArrayHasKey('docker/frankenphp/Caddyfile', $franken);
        self::assertStringContainsString('frankenphp', $franken['docker/php/Dockerfile']);

        $fpm = $generator->files(new DockerConfig(DockerRuntime::PhpFpmNginx));
        self::assertArrayHasKey('docker/nginx/default.conf', $fpm);
        self::assertArrayHasKey('docker/nginx/Dockerfile', $fpm);
        self::assertStringContainsString('fastcgi_pass app:9000', $fpm['docker/nginx/default.conf']);
        self::assertStringContainsString('nginx:', $fpm['compose.yaml']);

        $roadRunner = $generator->files(new DockerConfig(DockerRuntime::RoadRunner));
        self::assertArrayHasKey('docker/roadrunner/.rr.yaml', $roadRunner);
        self::assertArrayHasKey('docker/roadrunner/worker.php', $roadRunner);
        self::assertStringContainsString('/usr/bin/rr', $roadRunner['docker/php/Dockerfile']);
        self::assertStringContainsString('rr", "serve', $roadRunner['docker/php/Dockerfile']);

        $swoole = $generator->files(new DockerConfig(DockerRuntime::OpenSwoole));
        self::assertArrayHasKey('docker/openswoole/server.php', $swoole);
        self::assertStringContainsString('pecl install openswoole', $swoole['docker/php/Dockerfile']);
        self::assertStringContainsString('new Server', $swoole['docker/openswoole/server.php']);
    }

    public function testProductionRemovesDevelopmentBindMountsAndDatabaseHostPorts(): void
    {
        $compose = (new DockerComposeGenerator())->compose(new DockerConfig(
            DockerRuntime::FrankenPhp,
            [DockerService::Postgres, DockerService::Redis],
            true,
        ));

        self::assertStringNotContainsString('- .:/app', $compose);
        self::assertStringNotContainsString('127.0.0.1:5432:5432', $compose);
        self::assertStringNotContainsString('127.0.0.1:6379:6379', $compose);
        self::assertStringContainsString('APP_ENV: production', $compose);
        self::assertStringContainsString('restart: unless-stopped', $compose);
    }

    public function testDevelopmentInfrastructurePortsBindOnlyToLoopback(): void
    {
        $compose = (new DockerComposeGenerator())->compose(new DockerConfig(
            DockerRuntime::FrankenPhp,
            [DockerService::Postgres, DockerService::Redis],
        ));

        self::assertStringContainsString('127.0.0.1:5432:5432', $compose);
        self::assertStringContainsString('127.0.0.1:6379:6379', $compose);
    }
}
