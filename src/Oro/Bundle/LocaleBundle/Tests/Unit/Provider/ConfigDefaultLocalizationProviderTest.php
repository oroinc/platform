<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\ConfigDefaultLocalizationProvider;

class ConfigDefaultLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var ConfigDefaultLocalizationProvider $provider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationManager = $this->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

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
