<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;

class OrmTranslationResourceTest extends \PHPUnit\Framework\TestCase
{
    private string $locale;

    /** @var DynamicTranslationMetadataCache|\PHPUnit\Framework\MockObject\MockObject */
    private $metaCache;

    /** @var OrmTranslationResource */
    private $trResource;

    protected function setUp(): void
    {
        $this->locale = 'uk';
        $this->metaCache = $this->createMock(DynamicTranslationMetadataCache::class);

        $this->trResource = new OrmTranslationResource($this->locale, $this->metaCache);
    }

    public function testIsFresh()
    {
        $this->metaCache->expects($this->once())
            ->method('getTimestamp')
            ->with($this->locale)
            ->willReturn(false);

        $result = $this->trResource->isFresh(time());
        $this->assertTrue($result);
    }

    public function testMethods()
    {
        $this->assertStringEndsWith($this->locale, $this->trResource->getResource());
        $this->assertStringEndsWith($this->locale, (string)$this->trResource);
    }
}
