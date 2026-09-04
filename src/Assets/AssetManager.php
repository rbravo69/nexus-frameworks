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
        private readonly string $hotFile = 'public/hot',
    ) {
    }

    public function url(string $entry): string
    {
        $entry = ltrim(trim($entry), '/');

        if ($entry === '') {
            throw new \InvalidArgumentException('Asset entry cannot be empty.');
        }

        $devServer = $this->devServerUrl();

        if ($devServer !== null) {
            return $devServer . '/' . $entry;
        }

        $manifest = $this->manifest();
        $item = $manifest[$entry] ?? null;

        if (is_array($item) && isset($item['file']) && is_string($item['file'])) {
            return rtrim($this->publicPrefix, '/') . '/' . ltrim($item['file'], '/');
        }

        return rtrim($this->publicPrefix, '/') . '/' . basename($entry);
    }

    public function isHot(): bool
    {
        return $this->devServerUrl() !== null;
    }

    public function viteClientUrl(): ?string
    {
        $devServer = $this->devServerUrl();

        return $devServer === null ? null : $devServer . '/@vite/client';
    }

    /** @param string|array<array-key, mixed> $entries */
    public function tags(string|array $entries): string
    {
        $entries = is_string($entries) ? [$entries] : $entries;
        $tags = [];
        $client = $this->viteClientUrl();

        if ($client !== null) {
            $tags[] = '<script type="module" src="' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '"></script>';
        }

        foreach ($entries as $entry) {
            if (!is_string($entry) || trim($entry) === '') {
                continue;
            }

            $url = $this->url($entry);
            $escaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?: ''), '.css')) {
                $tags[] = '<link rel="stylesheet" href="' . $escaped . '">';
                continue;
            }

            $tags[] = '<script type="module" src="' . $escaped . '"></script>';
        }

        return implode("\n", $tags);
    }

    private function devServerUrl(): ?string
    {
        $path = rtrim($this->basePath, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($this->hotFile, '/\\'));

        if (!is_file($path)) {
            return null;
        }

        $url = trim((string) file_get_contents($path));

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return rtrim($url, '/');
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
