<?php

declare(strict_types=1);

namespace Nexus\Exception;

class RedirectResponseException extends \RuntimeException
{
    public function __construct(
        private readonly string $redirectTo,
        string $message = 'The request must be redirected.',
    ) {
        if ($redirectTo === '') {
            throw new \InvalidArgumentException('Redirect target cannot be empty.');
        }

        parent::__construct($message);
    }

    public function redirectTo(): string
    {
        return $this->redirectTo;
    }
}
