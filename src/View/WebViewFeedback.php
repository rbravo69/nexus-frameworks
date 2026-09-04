<?php

declare(strict_types=1);

namespace Nexus\View;

use Nexus\Session\SessionInterface;

final readonly class WebViewFeedback
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function old(string $key, mixed $default = null): mixed
    {
        $old = $this->session->get('_old_input', []);

        return is_array($old) ? ($old[$key] ?? $default) : $default;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        $value = $this->session->get('_errors', []);

        if (!is_array($value)) {
            return [];
        }

        $errors = [];

        foreach ($value as $key => $messages) {
            if (!is_string($key) || !is_array($messages)) {
                continue;
            }

            $normalized = array_values(array_filter($messages, 'is_string'));

            if ($normalized !== []) {
                $errors[$key] = $normalized;
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function errorMessages(string $key): array
    {
        return $this->errors()[$key] ?? [];
    }

    public function error(string $key, ?string $default = null): ?string
    {
        return $this->errorMessages($key)[0] ?? $default;
    }

    public function hasError(string $key): bool
    {
        return $this->errorMessages($key) !== [];
    }

    public function anyErrors(): bool
    {
        return $this->errors() !== [];
    }

    public function flash(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function hasFlash(string $key): bool
    {
        if (!$this->session->has($key)) {
            return false;
        }

        $old = $this->flashKeys('_nexus_flash_old');
        $new = $this->flashKeys('_nexus_flash_new');

        return in_array($key, $old, true) || in_array($key, $new, true);
    }

    /** @return list<string> */
    private function flashKeys(string $key): array
    {
        $value = $this->session->get($key, []);

        return is_array($value)
            ? array_values(array_filter($value, 'is_string'))
            : [];
    }
}
