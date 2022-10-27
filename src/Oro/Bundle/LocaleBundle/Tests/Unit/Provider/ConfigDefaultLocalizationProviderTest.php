<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\ConfigDefaultLocalizationProvider;

class ConfigDefaultLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var ConfigDefaultLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->provider = new ConfigDefaultLocalizationProvider($this->localizationManager);
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getCurrentLocalization());
    }
}
