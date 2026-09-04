<?php

declare(strict_types=1);

namespace Nexus\Tests\Auth;

use Nexus\Auth\AuthenticatableInterface;
use Nexus\Auth\AuthorizableInterface;
use Nexus\Auth\AuthManager;
use Nexus\Auth\EmailVerificationSigner;
use Nexus\Auth\PasswordHasher;
use Nexus\Auth\PasswordResetBroker;
use Nexus\Auth\PasswordResetTokenRepositoryInterface;
use Nexus\Auth\RememberMeManager;
use Nexus\Auth\RememberTokenRepositoryInterface;
use Nexus\Auth\UserProviderInterface;
use Nexus\Session\ArraySession;
use PHPUnit\Framework\TestCase;

final class AuthWebCompleteTest extends TestCase
{
    public function testPasswordHasherHashesVerifiesAndDetectsRehash(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('secret-password');

        self::assertTrue($hasher->verify('secret-password', $hash));
        self::assertFalse($hasher->verify('wrong', $hash));
        self::assertFalse($hasher->needsRehash($hash));
    }

    public function testAuthManagerSupportsIdRolesAndPermissions(): void
    {
        $auth = new AuthManager(new ArraySession(), new CompleteUserProvider());

        self::assertTrue($auth->loginUsingId(7));
        self::assertTrue($auth->hasRole('admin'));
        self::assertTrue($auth->hasAnyRole(['editor', 'admin']));
        self::assertTrue($auth->hasPermission('users.manage'));
    }

    public function testRememberMeTokensCanRecallAndRejectTampering(): void
    {
        $repository = new MemoryRememberRepository();
        $provider = new CompleteUserProvider();
        $remember = new RememberMeManager($repository, $provider);
        $user = $provider->retrieveById(7);
        self::assertInstanceOf(CompleteUser::class, $user);

        $token = $remember->issue($user);

        self::assertInstanceOf(CompleteUser::class, $remember->recall($token));
        self::assertNull($remember->recall($token . 'tampered'));
    }

    public function testPasswordResetTokensAreSingleUse(): void
    {
        $repository = new MemoryResetRepository();
        $broker = new PasswordResetBroker($repository, 10);
        $token = $broker->issue(7);

        self::assertTrue($broker->validate(7, $token));
        self::assertTrue($broker->consume(7, $token));
        self::assertFalse($broker->validate(7, $token));
    }

    public function testEmailVerificationTokensAreSignedAndBoundToUser(): void
    {
        $signer = new EmailVerificationSigner('test-secret', 10);
        $token = $signer->issue(7);

        self::assertTrue($signer->validate(7, $token));
        self::assertFalse($signer->validate(8, $token));
        self::assertFalse($signer->validate(7, $token . 'x'));
    }
}

final readonly class CompleteUser implements AuthenticatableInterface, AuthorizableInterface
{
    public function authIdentifier(): int
    {
        return 7;
    }

    public function roles(): array
    {
        return ['admin'];
    }

    public function permissions(): array
    {
        return ['users.manage'];
    }
}

final class CompleteUserProvider implements UserProviderInterface
{
    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return (string) $identifier === '7' ? new CompleteUser() : null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        return ($credentials['email'] ?? null) === 'user@example.com' ? new CompleteUser() : null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        return ($credentials['password'] ?? null) === 'secret';
    }
}

final class MemoryRememberRepository implements RememberTokenRepositoryInterface
{
    /** @var array<string, array{identifier:int|string, validator_hash:string, expires_at:\DateTimeImmutable}> */
    private array $records = [];

    public function store(int|string $identifier, string $selector, string $validatorHash, \DateTimeImmutable $expiresAt): void
    {
        $this->records[$selector] = [
            'identifier' => $identifier,
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
        ];
    }

    public function retrieve(string $selector): ?array
    {
        return $this->records[$selector] ?? null;
    }

    public function delete(string $selector): void
    {
        unset($this->records[$selector]);
    }

    public function deleteForUser(int|string $identifier): void
    {
        foreach ($this->records as $selector => $record) {
            if ((string) $record['identifier'] === (string) $identifier) {
                unset($this->records[$selector]);
            }
        }
    }
}

final class MemoryResetRepository implements PasswordResetTokenRepositoryInterface
{
    /** @var array<string, array{hash:string, expires_at:\DateTimeImmutable}> */
    private array $records = [];

    public function store(int|string $identifier, string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $this->records[(string) $identifier] = ['hash' => $tokenHash, 'expires_at' => $expiresAt];
    }

    public function retrieveHash(int|string $identifier): ?string
    {
        return $this->records[(string) $identifier]['hash'] ?? null;
    }

    public function expiresAt(int|string $identifier): ?\DateTimeImmutable
    {
        return $this->records[(string) $identifier]['expires_at'] ?? null;
    }

    public function delete(int|string $identifier): void
    {
        unset($this->records[(string) $identifier]);
    }
}
