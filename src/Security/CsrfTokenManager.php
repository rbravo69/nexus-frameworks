<?php

declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Session\SessionInterface;

final readonly class CsrfTokenManager
{
    private const SESSION_KEY = '_nexus_csrf_token';

    public function __construct(private SessionInterface $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->rotate();
    }

    public function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->put(self::SESSION_KEY, $token);

        return $token;
    }

    public function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals($this->token(), $token);
    }

    public function field(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s">',
            htmlspecialchars($this->token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}
