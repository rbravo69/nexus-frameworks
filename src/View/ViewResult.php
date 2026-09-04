<?php

declare(strict_types=1);

namespace Nexus\View;

final readonly class ViewResult
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $name,
        public array $data = [],
        public int $status = 200,
        public array $headers = [],
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('HTTP status must be between 100 and 599.');
        }
    }
}
