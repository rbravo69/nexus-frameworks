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
        private WebViewFeedback $feedback,
    ) {
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return [
            'assets' => $this->assets,
            'asset' => fn (string $entry): string => $this->assets->url($entry),
            'vite' => fn (string|array $entries): string => $this->assets->tags($entries),
            'csrf' => $this->csrf,
            'csrf_field' => fn (): string => $this->csrf->field(),
            'session' => $this->session,
            'auth' => $this->auth,
            'old' => fn (string $key, mixed $default = null): mixed => $this->feedback->old($key, $default),
            'errors' => fn (): array => $this->feedback->errors(),
            'error' => fn (string $key, ?string $default = null): ?string => $this->feedback->error($key, $default),
            'error_messages' => fn (string $key): array => $this->feedback->errorMessages($key),
            'has_error' => fn (string $key): bool => $this->feedback->hasError($key),
            'any_errors' => fn (): bool => $this->feedback->anyErrors(),
            'flash' => fn (string $key, mixed $default = null): mixed => $this->feedback->flash($key, $default),
            'has_flash' => fn (string $key): bool => $this->feedback->hasFlash($key),
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

    public function feedback(): WebViewFeedback
    {
        return $this->feedback;
    }
}
