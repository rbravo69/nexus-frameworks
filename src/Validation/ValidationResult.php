<?php

declare(strict_types=1);

namespace Nexus\Validation;

final readonly class ValidationResult
{
    /**
     * @param array<string, mixed> $validated
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        private array $validated,
        private array $errors,
    ) {
    }

    public function valid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validated;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function throwIfInvalid(): self
    {
        if (!$this->valid()) {
            throw new ValidationException($this->errors);
        }

        return $this;
    }
}
