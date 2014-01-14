<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\OrmTranslationMetadataCache;

class OrmTranslationMetadataCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrmTranslationMetadataCache */
    protected $metadataCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cacheImpl;

    public function setUp()
    {
        $this->cacheImpl = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save'])
            ->getMockForAbstractClass();
        $this->metadataCache = new OrmTranslationMetadataCache($this->cacheImpl);
    }

    public function testTimestamp()
    {
        $this->cacheImpl->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(1));

        $result = $this->metadataCache->getTimestamp('en_USSR');
        $this->assertEquals(1, $result);

        $this->cacheImpl
            ->expects($this->once())
            ->method('save');

        $this->metadataCache->updateTimestamp('en');
    }
}
