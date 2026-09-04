<?php

declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Http\Cookie;
use Nexus\Http\Request;
use Nexus\Http\Response;

final readonly class RememberCookie
{
    public function __construct(
        private string $name = 'NEXUS_REMEMBER',
        private int $ttlDays = 30,
        private bool $secure = false,
        private string $sameSite = 'Lax',
    ) {
    }

    public function tokenFrom(Request $request): ?string
    {
        return $request->cookie($this->name);
    }

    public function attach(Response $response, string $token): Response
    {
        return $response->withCookie($this->cookie($token, $this->ttlDays * 86400));
    }

    public function forget(Response $response): Response
    {
        return $response->withCookie(Cookie::forget($this->name));
    }

    private function cookie(string $value, int $maxAge): Cookie
    {
        return new Cookie(
            name: $this->name,
            value: $value,
            maxAge: $maxAge,
            secure: $this->secure,
            httpOnly: true,
            sameSite: $this->sameSite,
        );
    }
}
