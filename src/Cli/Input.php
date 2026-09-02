<?php

declare(strict_types=1);

namespace Nexus\Cli;

final readonly class Input
{
    /** @var list<string> */
    private array $arguments;

    /** @var array<string, string|true> */
    private array $options;

    /** @param list<string> $tokens */
    public function __construct(array $tokens = [])
    {
        [$this->arguments, $this->options] = $this->parse($tokens);
    }

    public function argument(int $index): ?string
    {
        return $this->arguments[$index] ?? null;
    }

    /** @return list<string> */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function option(string $name, ?string $default = null): ?string
    {
        $value = $this->options[$name] ?? null;

        return is_string($value) ? $value : $default;
    }

    /** @param list<string> $tokens
     *  @return array{list<string>, array<string, string|true>}
     */
    private function parse(array $tokens): array
    {
        $arguments = [];
        $options = [];
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            if (!str_starts_with($token, '--')) {
                $arguments[] = $token;
                continue;
            }

            $option = substr($token, 2);

            if ($option === '') {
                continue;
            }

            if (str_contains($option, '=')) {
                [$name, $value] = explode('=', $option, 2);
                $options[$name] = $value;
                continue;
            }

            $next = $tokens[$index + 1] ?? null;

            if ($next !== null && !str_starts_with($next, '-')) {
                $options[$option] = $next;
                $index++;
                continue;
            }

            $options[$option] = true;
        }

        return [$arguments, $options];
    }
}
