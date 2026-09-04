<?php

declare(strict_types=1);

namespace Nexus\Auth;

interface AuthorizableInterface
{
    /** @return list<string> */
    public function permissions(): array;
}
