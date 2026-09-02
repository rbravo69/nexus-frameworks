<?php

declare(strict_types=1);

namespace Nexus\Tests\Validation;

use Nexus\Validation\ValidationException;
use Nexus\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testItValidatesAndReturnsOnlyDeclaredFields(): void
    {
        $result = (new Validator())->validate(
            [
                'name' => 'Rafael',
                'age' => 40,
                'email' => 'rafael@example.test',
                'role' => 'admin',
                'ignored' => 'value',
            ],
            [
                'name' => 'required|string|min:3|max:80',
                'age' => 'required|integer|min:18',
                'email' => 'required|email',
                'role' => 'required|in:admin,user',
                'nickname' => 'nullable|string',
            ],
        );

        self::assertTrue($result->valid());
        self::assertSame(
            ['name' => 'Rafael', 'age' => 40, 'email' => 'rafael@example.test', 'role' => 'admin'],
            $result->validated(),
        );
    }

    public function testItCollectsErrorsAndExposesProblemDetails(): void
    {
        $result = (new Validator())->validate(
            ['email' => 'invalid', 'age' => 15],
            ['name' => 'required|string', 'email' => 'required|email', 'age' => 'integer|min:18'],
        );

        self::assertFalse($result->valid());
        self::assertArrayHasKey('name', $result->errors());
        self::assertArrayHasKey('email', $result->errors());
        self::assertArrayHasKey('age', $result->errors());

        try {
            $result->throwIfInvalid();
            self::fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            self::assertSame(422, $exception->problem('/users')->status);
            self::assertSame($result->errors(), $exception->errors());
        }
    }
}
