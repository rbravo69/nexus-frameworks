<?php

declare(strict_types=1);

namespace Nexus\Tests\Cache;

use Nexus\Cache\ArrayCache;
use Nexus\Cache\FilesystemCache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function testArrayCacheStoresDeletesAndRemembers(): void
    {
        $cache = new ArrayCache();
        $cache->set('answer', 42);

        self::assertTrue($cache->has('answer'));
        self::assertSame(42, $cache->get('answer'));
        self::assertSame('value', $cache->remember('remembered', 60, static fn (): string => 'value'));
        self::assertSame('value', $cache->remember('remembered', 60, static fn (): string => 'other'));

        $cache->delete('answer');
        self::assertFalse($cache->has('answer'));
    }

    public function testNonPositiveTtlDeletesItem(): void
    {
        $cache = new ArrayCache();
        $cache->set('key', 'value');
        $cache->set('key', 'other', 0);

        self::assertFalse($cache->has('key'));
    }

    public function testFilesystemCachePersistsValues(): void
    {
        $directory = sys_get_temp_dir() . '/nexus-cache-' . bin2hex(random_bytes(6));
        $cache = new FilesystemCache($directory);

        try {
            $cache->set('user', ['id' => 10, 'name' => 'Rafael'], 60);
            self::assertSame(['id' => 10, 'name' => 'Rafael'], $cache->get('user'));
            self::assertTrue($cache->has('user'));
            $cache->clear();
            self::assertFalse($cache->has('user'));
        } finally {
            if (is_dir($directory)) {
                @rmdir($directory);
            }
        }
    }
}
