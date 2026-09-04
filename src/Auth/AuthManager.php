<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Session\SessionInterface;

final class AuthManager
{
    private const SESSION_KEY = '_nexus_auth_id';

    private ?AuthenticatableInterface $resolvedUser = null;

    private bool $resolved = false;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserProviderInterface $provider,
    ) {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?AuthenticatableInterface
    {
        if ($this->resolved) {
            return $this->resolvedUser;
        }

        $this->resolved = true;
        $identifier = $this->session->get(self::SESSION_KEY);

        if (!is_int($identifier) && !is_string($identifier)) {
            return null;
        }

        $this->resolvedUser = $this->provider->retrieveById($identifier);

        return $this->resolvedUser;
    }

    /** @param array<string, mixed> $credentials */
    public function attempt(array $credentials, bool $regenerateSession = true): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null || !$this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $regenerateSession);

        return true;
    }

    public function loginUsingId(int|string $identifier, bool $regenerateSession = true): bool
    {
        $user = $this->provider->retrieveById($identifier);

        if ($user === null) {
            return false;
        }

        $this->login($user, $regenerateSession);

        return true;
    }

    public function login(AuthenticatableInterface $user, bool $regenerateSession = true): void
    {
        if ($regenerateSession) {
            $this->session->regenerate();
        }

        $this->session->put(self::SESSION_KEY, $user->authIdentifier());
        $this->resolved = true;
        $this->resolvedUser = $user;
    }

    public function logout(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->session->regenerate(true);
        $this->resolved = true;
        $this->resolvedUser = null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();

        return $user !== null && in_array($role, $user->roles(), true);
    }

    /** @param list<string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return array_intersect($roles, $user->roles()) !== [];
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->user();

        return $user instanceof AuthorizableInterface
            && in_array($permission, $user->permissions(), true);
    }
}
