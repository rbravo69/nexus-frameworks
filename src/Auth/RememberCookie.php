<?php

declare(strict_types=1);

namespace Nexus\Auth;

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
        $cookie = $request->header('Cookie');

        if ($cookie === null) {
            return null;
        }

        foreach (explode(';', $cookie) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');

            if ($name === $this->name && $value !== '') {
                return rawurldecode($value);
            }
        }

        return null;
    }

    public function attach(Response $response, string $token): Response
    {
        return $response->withHeader('Set-Cookie', $this->cookieValue($token, $this->ttlDays * 86400));
    }

    public function forget(Response $response): Response
    {
        return $response->withHeader('Set-Cookie', $this->cookieValue('', -3600));
    }

    private function cookieValue(string $value, int $maxAge): string
    {
        $parts = [
            $this->name . '=' . rawurlencode($value),
            'Path=/',
            'HttpOnly',
            'SameSite=' . $this->sameSite,
            'Max-Age=' . $maxAge,
        ];

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
