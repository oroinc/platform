<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    protected $localeSettings;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationManager;

    /** @var LocalizationChoicesProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationChoicesProvider;

    /** @var LocalizationSelectionType */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
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
            ->willReturn('en');

        $this->localizationManager = $this->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationChoicesProvider = $this->getMockBuilder(LocalizationChoicesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LocalizationSelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->localizationManager,
            $this->localizationChoicesProvider
        );
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => function () {
                },
                'compact' => false,
                'full_localization_list' => false,
                'placeholder' => '',
                'translatable_options' => false,
                'configs' => [
                    'placeholder' => 'oro.locale.localization.form.placeholder.select_localization',
                ],
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
        $this->localizationManager->method('getLocalization')
            ->will($this->returnValueMap([
                [1, true, $this->getEntity(Localization::class, ['id' => 1, 'name' => 'Localization 1'])],
                [2, true, $this->getEntity(Localization::class, ['id' => 2, 'name' => 'Localization 2'])],
                [3, true, $this->getEntity(Localization::class, ['id' => 3, 'name' => 'Localization 3'])],
            ]));

        $this->localizationManager->method('getLocalizations')->willReturn([
            1 => $this->getEntity(Localization::class, ['id' => 1, 'name' => 'Localization 1']),
            2 => $this->getEntity(Localization::class, ['id' => 2, 'name' => 'Localization 2']),
            3 => $this->getEntity(Localization::class, ['id' => 3, 'name' => 'Localization 3']),
        ]);

        $this->localizationChoicesProvider->method('getLocalizationChoices')->willReturn([
            'Localization 1' => 1,
            'Localization 2' => 2,
            'Localization 3' => 3,
        ]);

        $form = $this->factory->create(LocalizationSelectionType::class);

        $form->submit($submittedValue);

        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider()
    {
        return [
            'valid' => [
                'submittedValue' => '1',
                'isValid' => true
            ],
            'invalid' => [
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
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->setMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $choiceType->expects($this->any())->method('getParent')->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    LocalizationSelectionType::class => $this->formType,
                    OroChoiceType::class => $choiceType
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
