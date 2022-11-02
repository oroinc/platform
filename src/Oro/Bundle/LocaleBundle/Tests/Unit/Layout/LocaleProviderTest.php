<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Layout;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Layout\LocaleProvider;

class LocaleProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var LocaleProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

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
