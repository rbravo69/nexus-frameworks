<?php

declare(strict_types=1);

namespace Nexus\Session;

final class NativeSession implements SessionInterface
{
    public function __construct(
        private readonly string $name = 'NEXUSSESSID',
        private readonly bool $cookieSecure = false,
        private readonly string $sameSite = 'Lax',
    ) {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException('The PHP session cannot start after headers have been sent.');
        }

        session_name($this->name);
        session_set_cookie_params([
            'httponly' => true,
            'secure' => $this->cookieSecure,
            'samesite' => $this->normalizedSameSite(),
        ]);

        if (!session_start()) {
            throw new \RuntimeException('Unable to start the PHP session.');
        }
    }

    public function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists($key, $_SESSION);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $this->start();

        if (!session_regenerate_id($deleteOldSession)) {
            throw new \RuntimeException('Unable to regenerate the PHP session identifier.');
        }
    }

    public function invalidate(): void
    {
        $this->start();
        $_SESSION = [];
        $this->regenerate(true);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $new = $this->flashKeys('_nexus_flash_new');

        if (!in_array($key, $new, true)) {
            $new[] = $key;
        }

        $this->put('_nexus_flash_new', $new);
    }

    public function ageFlashData(): void
    {
        foreach ($this->flashKeys('_nexus_flash_old') as $key) {
            $this->forget($key);
        }

        $this->put('_nexus_flash_old', $this->flashKeys('_nexus_flash_new'));
        $this->put('_nexus_flash_new', []);
    }

    public function all(): array
    {
        $this->start();
        $data = [];

        foreach ($_SESSION as $key => $value) {
            if (is_string($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /** @return 'Lax'|'None'|'Strict' */
    private function normalizedSameSite(): string
    {
        return match (strtolower($this->sameSite)) {
            'none' => 'None',
            'strict' => 'Strict',
            default => 'Lax',
        };
    }

    /** @return list<string> */
    private function flashKeys(string $key): array
    {
        $value = $this->get($key, []);

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
