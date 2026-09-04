<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\Assets\AssetManager;
use Nexus\Auth\AuthManager;
use Nexus\Auth\ContainerUserProvider;
use Nexus\Auth\PasswordHasher;
use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityLoader;
use Nexus\Capability\CapabilityManifest;
use Nexus\Capability\CapabilityRegistry;
use Nexus\Capability\CapabilityResolver;
use Nexus\Container\Container;
use Nexus\Configuration\ConfigurationLoader;
use Nexus\Contracts\ConfigurationInterface;
use Nexus\Contracts\ContainerInterface;
use Nexus\Contracts\KernelInterface;
use Nexus\Contracts\LifecycleInterface;
use Nexus\Lifecycle\Lifecycle;
use Nexus\Module\ModuleRegistry;
use Nexus\Security\CsrfTokenManager;
use Nexus\Session\NativeSession;
use Nexus\Session\SessionInterface;
use Nexus\Validation\FormValidator;
use Nexus\Validation\Validator;
use Nexus\View\ViewFactory;
use Nexus\View\WebViewContext;
use Nexus\View\WebViewFeedback;

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
        ?CapabilityCatalog $capabilityCatalog = null,
        ?CapabilityRegistry $capabilities = null,
    ): Application {
        $environment ??= self::environmentVariable('APP_ENV', 'production');
        $debug ??= self::booleanEnvironmentVariable('APP_DEBUG', false);

        $runtimeEnvironment = new Environment($basePath, $environment, $debug);
        $configuration ??= (new ConfigurationLoader())->load(
            $configPath ?? $runtimeEnvironment->path('config'),
        );

        $modules ??= new ModuleRegistry();
        $capabilityCatalog ??= CapabilityCatalog::official();
        $capabilities ??= new CapabilityRegistry();
        $lifecycle ??= new Lifecycle();
        $container ??= new Container();

        $container
            ->instance(Environment::class, $runtimeEnvironment)
            ->instance(ConfigurationInterface::class, $configuration)
            ->instance(CapabilityCatalog::class, $capabilityCatalog)
            ->instance(CapabilityRegistry::class, $capabilities)
            ->instance(ModuleRegistry::class, $modules)
            ->instance(LifecycleInterface::class, $lifecycle);

        $application = new Application(
            environment: $runtimeEnvironment,
            configuration: $configuration,
            capabilities: $capabilities,
            modules: $modules,
            lifecycle: $lifecycle,
            container: $container,
        );

        $container
            ->instance(Application::class, $application)
            ->instance(KernelInterface::class, $application);

        self::registerViewRuntime($application, $runtimeEnvironment, $configuration);

        (new CapabilityLoader(
            new CapabilityResolver($capabilityCatalog),
            $container,
        ))->load(new CapabilityManifest($basePath), $capabilities);

        return $application;
    }

    private static function registerViewRuntime(
        Application $application,
        Environment $environment,
        ConfigurationInterface $configuration,
    ): void {
        $renderer = $configuration->get('frontend.renderer');

        if (!is_string($renderer) || !in_array($renderer, ['twig', 'php'], true)) {
            return;
        }

        $viewsPath = $configuration->get('frontend.views_path');
        $cachePath = $configuration->get('frontend.cache_path');
        $sessionName = $configuration->get('session.name', 'NEXUSSESSID');
        $sameSite = $configuration->get('session.same_site', 'Lax');
        $secure = $configuration->get('session.secure', false);

        $session = new NativeSession(
            name: is_string($sessionName) && $sessionName !== '' ? $sessionName : 'NEXUSSESSID',
            cookieSecure: is_bool($secure) ? $secure : false,
            sameSite: is_string($sameSite) && $sameSite !== '' ? $sameSite : 'Lax',
        );
        $assets = new AssetManager($environment->basePath);
        $csrf = new CsrfTokenManager($session);
        $auth = new AuthManager($session, new ContainerUserProvider($application->container()));
        $formValidator = new FormValidator(new Validator(), $session);
        $feedback = new WebViewFeedback($session);
        $context = new WebViewContext($assets, $csrf, $session, $auth, $feedback);
        $frameworkViews = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

        $application->container()
            ->instance(SessionInterface::class, $session)
            ->instance(NativeSession::class, $session)
            ->instance(AssetManager::class, $assets)
            ->instance(CsrfTokenManager::class, $csrf)
            ->instance(AuthManager::class, $auth)
            ->instance(PasswordHasher::class, new PasswordHasher())
            ->instance(FormValidator::class, $formValidator)
            ->instance(WebViewFeedback::class, $feedback)
            ->instance(WebViewContext::class, $context);

        ViewFactory::register(
            application: $application,
            renderer: $renderer,
            viewsPath: is_string($viewsPath) && $viewsPath !== ''
                ? $viewsPath
                : $environment->path('resources/views'),
            namespaces: is_dir($frameworkViews) ? ['nexus' => $frameworkViews] : [],
            cachePath: $renderer === 'twig'
                ? (is_string($cachePath) && $cachePath !== ''
                    ? $cachePath
                    : $environment->path('.nexus/cache/twig'))
                : null,
            debug: $environment->debug,
            context: $context,
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
