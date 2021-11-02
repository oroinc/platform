<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class DynamicTranslationMetadataCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheImpl;

    /** @var DynamicTranslationMetadataCache */
    private $metadataCache;

    protected function setUp(): void
    {
        $this->cacheImpl = $this->createMock(CacheProvider::class);

        $this->metadataCache = new DynamicTranslationMetadataCache($this->cacheImpl);
    }

    public function testTimestamp()
    {
        $this->cacheImpl->expects($this->once())
            ->method('fetch')
            ->willReturn(1);

        $result = $this->metadataCache->getTimestamp('en_USSR');
        $this->assertEquals(1, $result);

        $this->cacheImpl->expects($this->once())
            ->method('save');

        $this->metadataCache->updateTimestamp('en');
    }
}
