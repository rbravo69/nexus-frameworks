<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\Container\Container;
use Nexus\Configuration\ConfigurationLoader;
use Nexus\Contracts\ConfigurationInterface;
use Nexus\Contracts\ContainerInterface;
use Nexus\Contracts\KernelInterface;
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
        ?ContainerInterface $container = null,
    ): Application {
        $environment ??= self::environmentVariable('APP_ENV', 'production');
        $debug ??= self::booleanEnvironmentVariable('APP_DEBUG', false);

        $runtimeEnvironment = new Environment($basePath, $environment, $debug);
        $configuration ??= (new ConfigurationLoader())->load(
            $configPath ?? $runtimeEnvironment->path('config'),
        );

        $modules ??= new ModuleRegistry();
        $lifecycle ??= new Lifecycle();
        $container ??= new Container();

        $container
            ->instance(Environment::class, $runtimeEnvironment)
            ->instance(ConfigurationInterface::class, $configuration)
            ->instance(ModuleRegistry::class, $modules)
            ->instance(LifecycleInterface::class, $lifecycle);

        $application = new Application(
            environment: $runtimeEnvironment,
            configuration: $configuration,
            modules: $modules,
            lifecycle: $lifecycle,
            container: $container,
        );

        $container
            ->instance(Application::class, $application)
            ->instance(KernelInterface::class, $application);

        return $application;
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
