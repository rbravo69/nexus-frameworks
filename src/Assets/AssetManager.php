<?php

declare(strict_types=1);

namespace Nexus\Assets;

use JsonException;

final class AssetManager
{
    /** @var array<string, mixed>|null */
    private ?array $manifest = null;

    public function __construct(
        private readonly string $basePath,
        private readonly string $buildDirectory = 'public/build',
        private readonly string $publicPrefix = '/build',
    ) {
    }

    public function url(string $entry): string
    {
        $entry = ltrim(trim($entry), '/');

        if ($entry === '') {
            throw new \InvalidArgumentException('Asset entry cannot be empty.');
        }

        $manifest = $this->manifest();
        $item = $manifest[$entry] ?? null;

        if (is_array($item) && isset($item['file']) && is_string($item['file'])) {
            return rtrim($this->publicPrefix, '/') . '/' . ltrim($item['file'], '/');
        }

        return rtrim($this->publicPrefix, '/') . '/' . basename($entry);
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        foreach ($this->manifestCandidates() as $path) {
            if (!is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);

            if ($content === false) {
                continue;
            }

            try {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (!is_array($decoded)) {
                continue;
            }

            $manifest = [];

            foreach ($decoded as $key => $value) {
                if (is_string($key)) {
                    $manifest[$key] = $value;
                }
            }

            return $this->manifest = $manifest;
        }

        return $this->manifest = [];
    }

    /** @return list<string> */
    private function manifestCandidates(): array
    {
        $build = rtrim($this->basePath, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($this->buildDirectory, '/\\'));

        return [
            $build . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json',
            $build . DIRECTORY_SEPARATOR . 'manifest.json',
        ];
    }
}
