<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Layout;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Layout\LocaleProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocaleProviderTest extends TestCase
{
    private LocalizationHelper&MockObject $localizationHelper;
    private LocaleProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->provider = new LocaleProvider($this->localizationHelper);
    }

    public function testGetLocalizedValue(): void
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
