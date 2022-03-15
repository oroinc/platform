<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class DynamicTranslationMetadataCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheImpl;

    /** @var DynamicTranslationMetadataCache */
    private $metadataCache;

    protected function setUp(): void
    {
        $this->cacheImpl = $this->createMock(CacheItemPoolInterface::class);

        $this->metadataCache = new DynamicTranslationMetadataCache($this->cacheImpl);
    }

    public function testTimestamp()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $this->cacheImpl->expects($this->exactly(2))
            ->method('getItem')
            ->willReturn($cacheItem);
        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::once())
            ->method('get')
            ->willReturn(1);

        $result = $this->metadataCache->getTimestamp('en_USSR');
        $this->assertEquals(1, $result);

        $cacheItem->expects(self::once())
            ->method('set')
            ->willReturn($cacheItem);
        $this->cacheImpl->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $this->metadataCache->updateTimestamp('en');
    }
}
