<?php

declare(strict_types=1);

namespace Nexus\Cli;

use JsonException;
use Nexus\Exception\CliException;
use Nexus\Exception\InvalidInputException;

final readonly class CapabilityManifest
{
    public function __construct(
        private string $projectPath,
    ) {
    }

    /** @throws JsonException */
    public function add(string $capability): bool
    {
        $capability = $this->normalize($capability);
        $capabilities = $this->all();

        if (in_array($capability, $capabilities, true)) {
            return false;
        }

        $capabilities[] = $capability;
        sort($capabilities, SORT_STRING);
        $this->save($capabilities);

        return true;
    }

    /** @throws JsonException */
    public function remove(string $capability): bool
    {
        $capability = $this->normalize($capability);
        $capabilities = $this->all();
        $position = array_search($capability, $capabilities, true);

        if ($position === false) {
            return false;
        }

        unset($capabilities[$position]);
        $this->save(array_values($capabilities));

        return true;
    }

    /**
     * @param list<string> $capabilities
     * @throws JsonException
     */
    public function replace(array $capabilities): void
    {
        $normalized = [];

        foreach ($capabilities as $capability) {
            $normalized[] = $this->normalize($capability);
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);
        $this->save($normalized);
    }

    /**
     * @return list<string>
     * @throws JsonException
     */
    public function all(): array
    {
        $decoded = $this->load();

        if (!isset($decoded['capabilities'])) {
            return [];
        }

        if (!is_array($decoded['capabilities'])) {
            throw new CliException('Capability manifest has an invalid structure.');
        }

        $capabilities = [];

        foreach ($decoded['capabilities'] as $capability) {
            if (!is_string($capability)) {
                throw new CliException('Capability names in the manifest must be strings.');
            }

            $capabilities[] = $capability;
        }

        return $capabilities;
    }

    private function normalize(string $capability): string
    {
        $capability = strtolower(trim($capability));

        if (preg_match('/^[a-z][a-z0-9-]*$/', $capability) !== 1) {
            throw new InvalidInputException('Capability names use lowercase letters, numbers and dashes.');
        }

        return $capability;
    }

    /**
     * @param list<string> $capabilities
     * @throws JsonException
     */
    private function save(array $capabilities): void
    {
        $path = $this->path();
        $data = $this->load();
        $data['schema'] ??= 1;
        $data['capabilities'] = $capabilities;
        $payload = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . PHP_EOL;

        if (file_put_contents($path, $payload) === false) {
            throw new CliException(sprintf('Unable to write capability manifest "%s".', $path));
        }
    }

    private function path(): string
    {
        return $this->projectPath . DIRECTORY_SEPARATOR . 'nexus.json';
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    private function load(): array
    {
        $path = $this->path();

        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new CliException(sprintf('Unable to read capability manifest "%s".', $path));
        }

        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new CliException('Capability manifest has an invalid structure.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
