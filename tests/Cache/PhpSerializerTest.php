<?php

declare(strict_types=1);

namespace Nexus\Tests\Cache;

use Nexus\Cache\PhpSerializer;
use PHPUnit\Framework\TestCase;

final class PhpSerializerTest extends TestCase
{
    public function testObjectsAreNotHydratedByDefault(): void
    {
        WakeupProbe::$awakened = false;
        $payload = serialize(new WakeupProbe());

        $value = (new PhpSerializer())->unserialize($payload);

        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $value);
        self::assertFalse(WakeupProbe::$awakened);
    }
}

final class WakeupProbe
{
    public static bool $awakened = false;

    public function __wakeup(): void
    {
        self::$awakened = true;
    }
}
