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
        $old = $this->session->get('_old_input', []);
        $errors = $this->session->get('_errors', []);

        return [
            'assets' => $this->assets,
            'asset' => fn (string $entry): string => $this->assets->url($entry),
            'csrf' => $this->csrf,
            'session' => $this->session,
            'auth' => $this->auth,
            'old' => is_array($old) ? $old : [],
            'errors' => is_array($errors) ? $errors : [],
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
