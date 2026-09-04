<?php

declare(strict_types=1);

namespace Nexus\Module;

use Nexus\Application;
use Nexus\Contracts\ModuleInterface;
use Nexus\Exception\DuplicateModuleException;
use Nexus\View\TwigRenderer;
use Nexus\View\ViewFinder;
use Nexus\View\ViewRendererInterface;

final class ModuleRegistry
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

    private bool $locked = false;

    /** @var array<string, ModuleInterface>|null */
    private ?array $executionOrder = null;

    public function add(ModuleInterface $module): self
    {
        if ($this->locked) {
            throw new \LogicException('Modules cannot be added after registration has started.');
        }

        $name = trim($module->name());

        if ($name === '') {
            throw new \InvalidArgumentException('A module name cannot be empty.');
        }

        if (isset($this->modules[$name])) {
            throw DuplicateModuleException::named($name);
        }

        $this->modules[$name] = $module;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /** @return array<string, ModuleInterface> */
    public function all(): array
    {
        return $this->modules;
    }

    public function registerAll(Application $application): void
    {
        $this->locked = true;

        $this->executionOrder = (new ModuleDependencyResolver())->resolve($this->modules);

        foreach ($this->executionOrder as $module) {
            $module->register($application);
            $this->registerViewNamespace($application, $module);
        }
    }

    public function bootAll(Application $application): void
    {
        foreach ($this->orderedModules() as $module) {
            $module->boot($application);
        }
    }

    public function shutdownAll(Application $application): void
    {
        foreach (array_reverse($this->orderedModules(), true) as $module) {
            $module->shutdown($application);
        }
    }

    /** @return array<string, ModuleInterface> */
    private function orderedModules(): array
    {
        return $this->executionOrder
            ?? (new ModuleDependencyResolver())->resolve($this->modules);
    }

    private function registerViewNamespace(Application $application, ModuleInterface $module): void
    {
        $container = $application->container();

        if (!$container->has(ViewRendererInterface::class)) {
            return;
        }

        $finder = $container->get(ViewFinder::class);
        $renderer = $container->get(ViewRendererInterface::class);

        if (!$finder instanceof ViewFinder || !$renderer instanceof ViewRendererInterface) {
            return;
        }

        $name = trim($module->name());
        $namespace = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?? $name);
        $studly = implode('', array_map(
            static fn (string $part): string => ucfirst(strtolower($part)),
            preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [$name],
        ));

        $paths = array_unique([
            $application->environment()->path('modules/' . $studly . '/Views'),
            $application->environment()->path('modules/' . $name . '/Views'),
        ]);

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $finder->addNamespace($namespace, $path);

            if ($renderer instanceof TwigRenderer) {
                $renderer->addNamespace($namespace, $path);
            }
        }
    }
}
