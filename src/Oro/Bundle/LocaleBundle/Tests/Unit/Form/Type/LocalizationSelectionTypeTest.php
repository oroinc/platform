<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Intl\Locale\Locale;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class LocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject */
    protected $localeSettings;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationProvider;

    /** @var LocalizationChoicesProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationChoicesProvider;

    /** @var LocalizationSelectionType */
    protected $formType;

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

        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)
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

        $this->formType = new LocalizationSelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->localizationProvider,
            $this->localizationChoicesProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => function () {},
                'compact' => false,
                'localizations_list' => null,
                'additional_localizations' => null,
                'full_localization_list' => null,
            ]);

        $this->formType->configureOptions($resolver);

    }

    /**
     * @dataProvider submitFormDataProvider
     *
     * @param string $submittedValue
     * @param bool $isValid
     */
    public function testSubmitValidForm($submittedValue, $isValid)
    {
        $this->localizationProvider->method('getLocalization')
            ->will($this->returnValueMap([
                [1, $this->getEntity(Localization::class, ['id' => 1, 'name' => 'Localization 1'])],
                [2, $this->getEntity(Localization::class, ['id' => 2, 'name' => 'Localization 2'])],
                [3, $this->getEntity(Localization::class, ['id' => 3, 'name' => 'Localization 3'])],
            ]));

        $this->localizationProvider->method('getLocalizations')->willReturn([
            $this->getEntity(Localization::class, ['id' => 1, 'name' => 'Localization 1']),
            $this->getEntity(Localization::class, ['id' => 2, 'name' => 'Localization 2']),
            $this->getEntity(Localization::class, ['id' => 3, 'name' => 'Localization 3']),
        ]);

        $this->localizationChoicesProvider->method('getLocalizationChoices')->willReturn([
            1 => 'Localization 1',
            2 => 'Localization 2',
            3 => 'Localization 3',
        ]);

        $form = $this->factory->create($this->formType);

        $form->submit($submittedValue);

        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider()
    {
        return [
            'valid without default' => [
                'submittedValue' => '1',
                'isValid' => true
            ],
            'invalid without default' => [
                'submittedValue' => '10',
                'isValid' => false
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            $this->getValidatorExtension(true)
        ];
    }
}
