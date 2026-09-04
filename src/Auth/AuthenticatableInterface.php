<?php

declare(strict_types=1);

namespace Nexus\Auth;

interface AuthenticatableInterface
{
    public function authIdentifier(): int|string;

    /** @return list<string> */
    public function roles(): array;
}
