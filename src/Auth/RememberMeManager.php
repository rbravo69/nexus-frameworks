<?php

declare(strict_types=1);

namespace Nexus\Auth;

final readonly class RememberMeManager
{
    public function __construct(
        private RememberTokenRepositoryInterface $tokens,
        private UserProviderInterface $users,
        private int $ttlDays = 30,
    ) {
        if ($ttlDays < 1) {
            throw new \InvalidArgumentException('Remember-me TTL must be at least one day.');
        }
    }

    public function issue(AuthenticatableInterface $user): string
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d days', $this->ttlDays));
        $this->tokens->store(
            $user->authIdentifier(),
            $selector,
            hash('sha256', $validator),
            $expiresAt,
        );

        return $selector . ':' . $validator;
    }

    public function recall(string $token): ?AuthenticatableInterface
    {
        [$selector, $validator] = array_pad(explode(':', $token, 2), 2, '');

        if ($selector === '' || $validator === '') {
            return null;
        }

        $record = $this->tokens->retrieve($selector);

        if ($record === null || $record['expires_at'] < new \DateTimeImmutable()) {
            return null;
        }

        if (!hash_equals($record['validator_hash'], hash('sha256', $validator))) {
            $this->tokens->delete($selector);
            return null;
        }

        return $this->users->retrieveById($record['identifier']);
    }

    public function forget(string $token): void
    {
        $selector = explode(':', $token, 2)[0];

        if ($selector !== '') {
            $this->tokens->delete($selector);
        }
    }
}
