<?php

declare(strict_types=1);

namespace Nexus\Rest;

final readonly class ProblemDetails
{
    /** @param array<string, mixed> $extensions */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $extensions = [],
    ) {
        if ($status < 400 || $status > 599) {
            throw new \InvalidArgumentException('Problem Details status must be between 400 and 599.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== null) {
            $payload['detail'] = $this->detail;
        }

        if ($this->instance !== null) {
            $payload['instance'] = $this->instance;
        }

        return [...$payload, ...$this->extensions];
    }
}
