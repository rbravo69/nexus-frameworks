<?php

declare(strict_types=1);

namespace Nexus\View;

use Nexus\Assets\AssetManager;
use Nexus\Auth\AuthManager;
use Nexus\Security\CsrfTokenManager;
use Nexus\Session\SessionInterface;

final readonly class WebViewContext
{
    public function __construct(
        private AssetManager $assets,
        private CsrfTokenManager $csrf,
        private SessionInterface $session,
        private AuthManager $auth,
    ) {
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return [
            'assets' => $this->assets,
            'asset' => fn (string $entry): string => $this->assets->url($entry),
            'csrf' => $this->csrf,
            'csrf_field' => fn (): string => $this->csrf->field(),
            'session' => $this->session,
            'auth' => $this->auth,
            'old' => function (string $key, mixed $default = null): mixed {
                $old = $this->session->get('_old_input', []);

                return is_array($old) ? ($old[$key] ?? $default) : $default;
            },
            'errors' => function (): array {
                $errors = $this->session->get('_errors', []);

                return is_array($errors) ? $errors : [];
            },
            'flash' => fn (string $key, mixed $default = null): mixed => $this->session->get($key, $default),
        ];
    }

    public function assets(): AssetManager
    {
        return $this->assets;
    }

    public function csrf(): CsrfTokenManager
    {
        return $this->csrf;
    }

    public function session(): SessionInterface
    {
        return $this->session;
    }

    public function auth(): AuthManager
    {
        return $this->auth;
    }
}
