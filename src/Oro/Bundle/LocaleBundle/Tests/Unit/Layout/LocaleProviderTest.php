<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Layout;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Layout\LocaleProvider;

class LocaleProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var LocaleProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocaleProvider($this->localizationHelper);
    }

    public function testGetLocalizedValue()
    {
        $value = new LocalizedFallbackValue();
        $collection = new ArrayCollection();

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($collection)
            ->willReturn($value);

        $this->assertSame($value, $this->provider->getLocalizedValue($collection));
    }
}
