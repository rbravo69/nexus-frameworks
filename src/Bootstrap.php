<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\Configuration\ConfigurationLoader;
use Nexus\Contracts\ConfigurationInterface;
use Nexus\Contracts\LifecycleInterface;
use Nexus\Lifecycle\Lifecycle;
use Nexus\Module\ModuleRegistry;

final class Bootstrap
{
    public static function create(
        string $basePath,
        ?string $environment = null,
        ?bool $debug = null,
        ?string $configPath = null,
        ?ConfigurationInterface $configuration = null,
        ?ModuleRegistry $modules = null,
        ?LifecycleInterface $lifecycle = null,
    ): Application {
        $environment ??= self::environmentVariable('APP_ENV', 'production');
        $debug ??= self::booleanEnvironmentVariable('APP_DEBUG', false);

        $runtimeEnvironment = new Environment($basePath, $environment, $debug);
        $configuration ??= (new ConfigurationLoader())->load(
            $configPath ?? $runtimeEnvironment->path('config'),
        );

        return new Application(
            environment: $runtimeEnvironment,
            configuration: $configuration,
            modules: $modules ?? new ModuleRegistry(),
            lifecycle: $lifecycle ?? new Lifecycle(),
        );
    }

    private static function environmentVariable(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function booleanEnvironmentVariable(string $key, bool $default): bool
    {
        $value = self::environmentVariable($key, $default ? 'true' : 'false');
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }
}
