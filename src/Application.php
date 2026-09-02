<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\Contracts\ConfigurationInterface;
use Nexus\Contracts\ContainerInterface;
use Nexus\Contracts\KernelInterface;
use Nexus\Contracts\LifecycleInterface;
use Nexus\Exception\ApplicationStateException;
use Nexus\Lifecycle\LifecycleEvent;
use Nexus\Module\ModuleRegistry;
use Throwable;

final class Application implements KernelInterface
{
    private ApplicationState $state = ApplicationState::Created;

    public function __construct(
        private readonly Environment $environment,
        private readonly ConfigurationInterface $configuration,
        private readonly ModuleRegistry $modules,
        private readonly LifecycleInterface $lifecycle,
        private readonly ContainerInterface $container,
    ) {
    }

    public function boot(): void
    {
        if ($this->state === ApplicationState::Booted) {
            return;
        }

        if ($this->state !== ApplicationState::Created) {
            throw new ApplicationStateException(sprintf(
                'Application cannot boot from the "%s" state.',
                $this->state->value,
            ));
        }

        $this->state = ApplicationState::Booting;

        try {
            $this->lifecycle->dispatch(LifecycleEvent::BeforeBoot, $this);
            $this->modules->registerAll($this);
            $this->lifecycle->dispatch(LifecycleEvent::AfterRegister, $this);
            $this->modules->bootAll($this);
            $this->state = ApplicationState::Booted;
            $this->lifecycle->dispatch(LifecycleEvent::AfterBoot, $this);
        } catch (Throwable $exception) {
            $this->state = ApplicationState::Failed;

            throw $exception;
        }
    }

    public function shutdown(): void
    {
        if ($this->state === ApplicationState::Terminated) {
            return;
        }

        if ($this->state !== ApplicationState::Booted) {
            throw new ApplicationStateException(sprintf(
                'Application cannot shut down from the "%s" state.',
                $this->state->value,
            ));
        }

        $this->state = ApplicationState::ShuttingDown;

        try {
            $this->lifecycle->dispatch(LifecycleEvent::BeforeShutdown, $this);
            $this->modules->shutdownAll($this);
            $this->state = ApplicationState::Terminated;
            $this->lifecycle->dispatch(LifecycleEvent::AfterShutdown, $this);
        } catch (Throwable $exception) {
            $this->state = ApplicationState::Failed;

            throw $exception;
        }
    }

    public function state(): ApplicationState
    {
        return $this->state;
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function config(): ConfigurationInterface
    {
        return $this->configuration;
    }

    public function modules(): ModuleRegistry
    {
        return $this->modules;
    }

    public function lifecycle(): LifecycleInterface
    {
        return $this->lifecycle;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
