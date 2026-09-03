<?php

declare(strict_types=1);

namespace Nexus\Capability;

use Nexus\Contracts\CapabilityInterface;
use Nexus\Contracts\ContainerInterface;
use Nexus\Exception\CapabilityLoadException;

final readonly class CapabilityLoader
{
    public function __construct(
        private CapabilityResolver $resolver,
        private ContainerInterface $container,
    ) {
    }

    public function load(CapabilityManifest $manifest, CapabilityRegistry $registry): void
    {
        foreach ($this->resolver->resolve($manifest->all()) as $definition) {
            if (!class_exists($definition->provider)) {
                throw CapabilityLoadException::providerNotFound($definition);
            }

            $provider = $this->container->get($definition->provider);

            if (!$provider instanceof CapabilityInterface) {
                throw CapabilityLoadException::invalidProvider($definition);
            }

            $registry->add($definition->name, $provider);
        }
    }
}
