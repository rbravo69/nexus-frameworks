<?php

declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Exception\RedirectResponseException;

final class FormValidationException extends RedirectResponseException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        private readonly array $errors,
        string $redirectTo,
    ) {
        parent::__construct($redirectTo, 'The submitted form data is invalid.');
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
