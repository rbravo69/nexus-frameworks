<?php

declare(strict_types=1);

namespace Nexus\Validation;

final class FormValidationException extends \RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        private readonly array $errors,
        private readonly string $redirectTo,
    ) {
        parent::__construct('The submitted form data is invalid.');
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function redirectTo(): string
    {
        return $this->redirectTo;
    }
}
