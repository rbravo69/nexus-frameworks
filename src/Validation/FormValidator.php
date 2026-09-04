<?php

declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Http\Request;
use Nexus\Session\SessionInterface;

final readonly class FormValidator
{
    public function __construct(
        private Validator $validator,
        private SessionInterface $session,
    ) {
    }

    /**
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    public function validate(Request $request, array $rules, ?string $redirectTo = null): array
    {
        $input = $request->input();
        $result = $this->validator->validate($input, $rules);

        if ($result->valid()) {
            return $result->validated();
        }

        $this->session->flash('_old_input', $this->safeOldInput($input));
        $this->session->flash('_errors', $result->errors());

        throw new FormValidationException(
            $result->errors(),
            $redirectTo ?? $request->header('referer', '/') ?? '/',
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function safeOldInput(array $input): array
    {
        foreach (['_token', 'password', 'password_confirmation', 'current_password'] as $sensitive) {
            unset($input[$sensitive]);
        }

        return $input;
    }
}
