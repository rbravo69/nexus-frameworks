<?php

declare(strict_types=1);

namespace Nexus\Fake;

final class FakeGenerator
{
    private int $state;

    /** @var non-empty-list<string> */
    private array $firstNames = ['Ana', 'Carlos', 'Elena', 'Jorge', 'Lucia', 'Mateo', 'Rafael', 'Sofia'];

    /** @var non-empty-list<string> */
    private array $lastNames = ['Bravo', 'Garcia', 'Lopez', 'Martinez', 'Perez', 'Ramirez', 'Santos', 'Torres'];

    public function __construct(int $seed = 1)
    {
        $this->state = $seed & 0x7fffffff;
        if ($this->state === 0) {
            $this->state = 1;
        }
    }

    public function integer(int $min = 0, int $max = 2147483647): int
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Fake integer minimum cannot exceed maximum.');
        }

        $next = $this->next();
        $range = $max - $min + 1;
        return $min + ($next % $range);
    }

    public function boolean(int $truePercentage = 50): bool
    {
        if ($truePercentage < 0 || $truePercentage > 100) {
            throw new \InvalidArgumentException('True percentage must be between 0 and 100.');
        }

        return $this->integer(1, 100) <= $truePercentage;
    }

    /**
     * @template T
     * @param non-empty-list<T> $values
     * @return T
     */
    public function oneOf(array $values): mixed
    {
        return $values[$this->integer(0, count($values) - 1)];
    }

    public function name(): string
    {
        return $this->oneOf($this->firstNames) . ' ' . $this->oneOf($this->lastNames);
    }

    public function email(?string $name = null): string
    {
        $local = strtolower($name ?? $this->name());
        $local = preg_replace('/[^a-z0-9]+/', '.', $local) ?? 'user';
        $local = trim($local, '.');
        return $local . $this->integer(1, 9999) . '@example.test';
    }

    public function word(): string
    {
        return $this->oneOf(['alpha', 'bravo', 'delta', 'nexus', 'orbit', 'pixel', 'terra', 'vector']);
    }

    private function next(): int
    {
        $this->state = (int) (($this->state * 1103515245 + 12345) & 0x7fffffff);
        return $this->state;
    }
}
