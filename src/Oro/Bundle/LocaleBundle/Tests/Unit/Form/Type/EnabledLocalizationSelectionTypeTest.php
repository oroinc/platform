<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Locale\Locale;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Form\Type\EnabledLocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class EnabledLocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizationSelectionType
     */
    protected $formType;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
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

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder(LocaleSettings::class)
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
