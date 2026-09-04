<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Contracts\ContainerInterface;

final readonly class ContainerUserProvider implements UserProviderInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return $this->provider()->retrieveById($identifier);
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        return $this->provider()->retrieveByCredentials($credentials);
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        return $this->provider()->validateCredentials($user, $credentials);
    }

    private function provider(): UserProviderInterface
    {
        if (!$this->container->has(UserProviderInterface::class)) {
            return new NullUserProvider();
        }

        $provider = $this->container->get(UserProviderInterface::class);

        return $provider instanceof UserProviderInterface && !$provider instanceof self
            ? $provider
            : new NullUserProvider();
    }
}
