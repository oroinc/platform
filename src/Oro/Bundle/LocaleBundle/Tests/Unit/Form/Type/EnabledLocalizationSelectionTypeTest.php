<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Locale\Locale;

use Oro\Bundle\LocaleBundle\Form\Type\EnabledLocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class EnabledLocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizationSelectionType
     */
    protected $formType;

    /**
     * @var \Oro\Bundle\ConfigBundle\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \Oro\Bundle\LocaleBundle\Model\LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationProvider;

    /**
     * @var LocalizationChoicesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationChoicesProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(Locale::getDefault());

        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationChoicesProvider = $this->getMockBuilder(LocalizationChoicesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new EnabledLocalizationSelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->localizationProvider,
            $this->localizationChoicesProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals(EnabledLocalizationSelectionType::NAME, $this->formType->getName());
    }

    public function testGetCurrencySelectorConfigKey()
    {
        $this->assertEquals(
            EnabledLocalizationSelectionType::LOCALIZATION_SELECTOR_CONFIG_KEY,
            $this->formType->getLocalizationSelectorConfigKey()
        );
    }
}
