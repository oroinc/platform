<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

class CurrentLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCurrentLocalizationAndNoExtensions()
    {
        $provider = new CurrentLocalizationProvider([]);

        $this->assertNull($provider->getCurrentLocalization());
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $extension1 = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension2 = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension3 = $this->createMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(null);
        $extension2->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $extension3->expects(self::never())
            ->method('getCurrentLocalization');

        $provider = new CurrentLocalizationProvider([
            $extension1,
            $extension2,
            $extension3
        ]);

        $this->assertSame($localization, $provider->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWhenAllExtensionsDidNotReturnLocalization()
    {
        $extension1 = $this->createMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $provider = new CurrentLocalizationProvider([
            $extension1
        ]);

        $this->assertNull($provider->getCurrentLocalization());
    }

    public function testSetCurrentLocalization()
    {
        $localization1 = new Localization();
        $localization2 = new Localization();

        $extension = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($localization1);

        $provider = new CurrentLocalizationProvider([$extension]);

        $provider->setCurrentLocalization($localization2);
        $this->assertSame($localization2, $provider->getCurrentLocalization());
        // test that the result is cached
        $this->assertSame($localization2, $provider->getCurrentLocalization());

        $provider->setCurrentLocalization(null);
        $this->assertSame($localization1, $provider->getCurrentLocalization());
    }
}
