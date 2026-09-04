<?php

declare(strict_types=1);

namespace Nexus\Tests\View;

use Nexus\Session\ArraySession;
use Nexus\View\WebViewFeedback;
use PHPUnit\Framework\TestCase;

final class WebViewFeedbackTest extends TestCase
{
    public function testItReadsOldInputAndValidationErrors(): void
    {
        $session = new ArraySession([
            '_old_input' => ['email' => 'rafael@example.com'],
            '_errors' => [
                'email' => ['Email is invalid.', 'Email is required.'],
                7 => ['ignored'],
                'broken' => 'ignored',
            ],
        ]);
        $feedback = new WebViewFeedback($session);

        self::assertSame('rafael@example.com', $feedback->old('email'));
        self::assertSame('fallback', $feedback->old('name', 'fallback'));
        self::assertTrue($feedback->hasError('email'));
        self::assertTrue($feedback->anyErrors());
        self::assertSame('Email is invalid.', $feedback->error('email'));
        self::assertSame(
            ['Email is invalid.', 'Email is required.'],
            $feedback->errorMessages('email'),
        );
        self::assertSame(['email' => ['Email is invalid.', 'Email is required.']], $feedback->errors());
    }

    public function testItDistinguishesFlashValuesFromPersistentSessionValues(): void
    {
        $session = new ArraySession(['locale' => 'es']);
        $session->flash('success', 'Saved');
        $feedback = new WebViewFeedback($session);

        self::assertSame('Saved', $feedback->flash('success'));
        self::assertTrue($feedback->hasFlash('success'));
        self::assertFalse($feedback->hasFlash('locale'));
        self::assertSame('es', $feedback->flash('locale'));
    }
}
