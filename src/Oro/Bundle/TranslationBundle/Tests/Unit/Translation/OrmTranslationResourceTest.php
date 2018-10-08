<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;

class OrmTranslationResourceTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrmTranslationResource */
    protected $trResource;

    /** @var string */
    protected $locale;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metaCache;

    protected function setUp()
    {
        $this->locale    = 'uk';
        $this->metaCache = $this->getMockBuilder(
            'Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->trResource = new OrmTranslationResource($this->locale, $this->metaCache);
    }

    public function testIsFresh()
    {
        $this->metaCache->expects($this->once())
            ->method('getTimestamp')
            ->with($this->locale)
            ->will($this->returnValue(false));

        $result = $this->trResource->isFresh(time());
        $this->assertTrue($result);
    }

    public function testMethods()
    {
        $this->assertStringEndsWith($this->locale, $this->trResource->getResource());
        $this->assertStringEndsWith($this->locale, (string)$this->trResource);
    }
}
