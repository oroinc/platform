<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;

class MemoryCacheTest extends \PHPUnit\Framework\TestCase
{
    private MemoryCache $memoryCache;

    protected function setUp(): void
    {
        $this->memoryCache = new MemoryCache();
    }

    public function testCacheOperations(): void
    {
        self::assertFalse($this->memoryCache->has('key1'));
        self::assertNull($this->memoryCache->get('key1'));
        self::assertSame(1, $this->memoryCache->get('key1', 1));

        $this->memoryCache->set('key1', 123);
        self::assertTrue($this->memoryCache->has('key1'));
        self::assertSame(123, $this->memoryCache->get('key1', 1));
        self::assertFalse($this->memoryCache->has('key2'));
        self::assertNull($this->memoryCache->get('key2'));

        $this->memoryCache->set('key1', 234);
        self::assertTrue($this->memoryCache->has('key1'));
        self::assertSame(234, $this->memoryCache->get('key1', 1));

        $this->memoryCache->delete('key1');
        self::assertFalse($this->memoryCache->has('key1'));
        self::assertNull($this->memoryCache->get('key1'));

        $this->memoryCache->set('key1', 1);
        $this->memoryCache->set('key2', 2);
        self::assertTrue($this->memoryCache->has('key1'));
        self::assertSame(1, $this->memoryCache->get('key1', 1));
        self::assertTrue($this->memoryCache->has('key2'));
        self::assertSame(2, $this->memoryCache->get('key2', 1));

        $this->memoryCache->deleteAll();
        self::assertFalse($this->memoryCache->has('key1'));
        self::assertNull($this->memoryCache->get('key1'));
        self::assertFalse($this->memoryCache->has('key2'));
        self::assertNull($this->memoryCache->get('key2'));

        $this->memoryCache->set('key1', 1);
        self::assertTrue($this->memoryCache->has('key1'));
        $this->memoryCache->reset();
        self::assertFalse($this->memoryCache->has('key1'));
        self::assertNull($this->memoryCache->get('key1'));
    }
}
