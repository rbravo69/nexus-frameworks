<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use JsonException;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Exception\InvalidInputException;

final readonly class ConfigCommand implements CommandInterface
{
    public function __construct(private string $workingDirectory)
    {
    }

    public function name(): string
    {
        return 'config';
    }

    public function description(): string
    {
        return 'Inspect the effective Nexus project manifest.';
    }

    public function usage(): string
    {
        return 'nexus config [key]';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $path = rtrim($this->workingDirectory, '/\\') . DIRECTORY_SEPARATOR . 'nexus.json';

        if (!is_file($path)) {
            throw new InvalidInputException('nexus.json was not found in the working directory.');
        }

        try {
            $config = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidInputException('nexus.json is not valid JSON: ' . $exception->getMessage());
        }

        if (!is_array($config)) {
            throw new InvalidInputException('nexus.json must contain a JSON object.');
        }

        $key = $input->argument(0);

        if ($key !== null) {
            $value = $this->value($config, $key);

            if ($value === null) {
                throw new InvalidInputException(sprintf('Config key "%s" was not found.', $key));
            }

            $output->writeln($this->render($value));

            return ExitCode::Success;
        }

        foreach ($this->flatten($config) as $name => $value) {
            $output->writeln(sprintf('%s=%s', $name, $this->render($value)));
        }

        return ExitCode::Success;
    }

    /** @param array<string, mixed> $config */
    private function value(array $config, string $key): mixed
    {
        $value = $config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /** @param array<string, mixed> $values
     *  @return array<string, mixed>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $name = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value) && !array_is_list($value)) {
                $flat += $this->flatten($value, $name);
                continue;
            }

            $flat[$name] = $value;
        }

        ksort($flat);

        return $flat;
    }

    private function render(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
