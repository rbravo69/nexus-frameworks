<?php

declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Rest\ProblemDetails;

final class ValidationException extends \RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function problem(?string $instance = null): ProblemDetails
    {
        return new ProblemDetails(
            'https://nexusphp.dev/problems/validation',
            'Validation failed',
            422,
            'One or more fields are invalid.',
            $instance,
            ['errors' => $this->errors],
        );
    }
}
