<?php

declare(strict_types=1);

namespace Nexus\Validation;

final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, string|list<string>> $rules
     */
    public function validate(array $input, array $rules): ValidationResult
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $exists = array_key_exists($field, $input);
            $value = $exists ? $input[$field] : null;
            $nullable = in_array('nullable', $ruleList, true);

            if (!$exists && in_array('required', $ruleList, true)) {
                $errors[$field][] = 'The field is required.';
                continue;
            }

            if (!$exists) {
                continue;
            }

            if ($value === null && $nullable) {
                $validated[$field] = null;
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                $message = $this->validateRule($field, $value, $rule);

                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        return new ValidationResult($validated, $errors);
    }

    private function validateRule(string $field, mixed $value, string $rule): ?string
    {
        [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($name) {
            'string' => is_string($value) ? null : 'The field must be a string.',
            'integer' => is_int($value) ? null : 'The field must be an integer.',
            'numeric' => is_int($value) || is_float($value) ? null : 'The field must be numeric.',
            'boolean' => is_bool($value) ? null : 'The field must be a boolean.',
            'array' => is_array($value) ? null : 'The field must be an array.',
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? null
                : 'The field must be a valid email address.',
            'min' => $this->validateMin($value, $argument),
            'max' => $this->validateMax($value, $argument),
            'in' => $this->validateIn($value, $argument),
            default => throw new \InvalidArgumentException(sprintf('Unknown validation rule "%s" for field "%s".', $name, $field)),
        };
    }

    private function validateMin(mixed $value, ?string $argument): ?string
    {
        $limit = $this->numericArgument('min', $argument);
        $size = $this->sizeOf($value);

        return $size !== null && $size >= $limit ? null : sprintf('The field must be at least %s.', $argument);
    }

    private function validateMax(mixed $value, ?string $argument): ?string
    {
        $limit = $this->numericArgument('max', $argument);
        $size = $this->sizeOf($value);

        return $size !== null && $size <= $limit ? null : sprintf('The field may not be greater than %s.', $argument);
    }

    private function validateIn(mixed $value, ?string $argument): ?string
    {
        if ($argument === null || $argument === '') {
            throw new \InvalidArgumentException('The in rule requires at least one value.');
        }

        return in_array((string) $value, explode(',', $argument), true)
            ? null
            : 'The selected value is invalid.';
    }

    private function numericArgument(string $rule, ?string $argument): float
    {
        if ($argument === null || $argument === '' || !is_numeric($argument)) {
            throw new \InvalidArgumentException(sprintf('The %s rule requires a numeric argument.', $rule));
        }

        return (float) $argument;
    }

    private function sizeOf(mixed $value): ?float
    {
        return match (true) {
            is_string($value) => (float) mb_strlen($value),
            is_int($value), is_float($value) => (float) $value,
            is_array($value) => (float) count($value),
            default => null,
        };
    }
}
