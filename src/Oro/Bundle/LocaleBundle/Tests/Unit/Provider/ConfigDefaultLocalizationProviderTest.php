<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\ConfigDefaultLocalizationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigDefaultLocalizationProviderTest extends TestCase
{
    private LocalizationManager&MockObject $localizationManager;
    private ConfigDefaultLocalizationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->provider = new ConfigDefaultLocalizationProvider($this->localizationManager);
    }

    public function testGetCurrentLocalization(): void
    {
        $localization = new Localization();

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getCurrentLocalization());
    }
}
