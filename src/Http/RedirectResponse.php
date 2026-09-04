<?php

declare(strict_types=1);

namespace Nexus\Http;

final class RedirectResponse extends Response
{
    /** @param array<string, string> $headers
     *  @param array<string, mixed> $flash
     */
    public function __construct(
        string $location,
        int $status = 302,
        array $headers = [],
        private readonly array $flash = [],
    ) {
        if ($location === '') {
            throw new \InvalidArgumentException('Redirect location cannot be empty.');
        }

        parent::__construct($status, ['location' => $location, ...$headers]);
    }

    /** @param array<string, string> $headers */
    public static function to(string $location, int $status = 302, array $headers = []): self
    {
        return new self($location, $status, $headers);
    }

    /** @param array<string, string> $headers */
    public static function back(Request $request, string $fallback = '/', int $status = 302, array $headers = []): self
    {
        $location = $request->header('Referer', $fallback);

        return new self(is_string($location) && $location !== '' ? $location : $fallback, $status, $headers);
    }

    public function with(string $key, mixed $value): self
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Flash key cannot be empty.');
        }

        $headers = $this->headers();
        $location = $headers['location'] ?? '/';
        unset($headers['location']);

        return new self(
            $location,
            $this->status(),
            $headers,
            [...$this->flash, $key => $value],
        );
    }

    /** @return array<string, mixed> */
    public function flashData(): array
    {
        return $this->flash;
    }
}
