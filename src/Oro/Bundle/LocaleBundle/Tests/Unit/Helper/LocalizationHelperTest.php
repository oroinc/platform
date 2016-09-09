<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\FallbackTrait;

class LocalizationHelperTest extends \PHPUnit_Framework_TestCase
{
    use FallbackTrait;

    /** @var LocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationManager;

    /** @var CurrentLocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $currentLocalizationProvider;

    /** @var LocalizationHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationManager = $this->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currentLocalizationProvider = $this->getMockBuilder(CurrentLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new LocalizationHelper($this->localizationManager, $this->currentLocalizationProvider);
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $this->currentLocalizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->helper->getCurrentLocalization());
    }

    public function testGetLocalizations()
    {
        $localizations = [new Localization()];

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $this->assertSame($localizations, $this->helper->getLocalizations());
    }

    public function testGetLocalizedValue()
    {
        $this->assertFallbackValue($this->helper, 'getLocalizedValue');
    }
}
