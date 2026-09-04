<?php

declare(strict_types=1);

namespace Nexus\Auth;

final class NullUserProvider implements UserProviderInterface
{
    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        return null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        return false;
    }
}
