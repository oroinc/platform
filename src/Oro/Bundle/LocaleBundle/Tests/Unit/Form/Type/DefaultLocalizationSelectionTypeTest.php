<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Translator;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\DefaultLocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class DefaultLocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationProvider;

    /**
     * @var LocalizationChoicesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationChoicesProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMock(RequestStack::class);

        $this->localizationChoicesProvider = $this->getMockBuilder(LocalizationChoicesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $map = [
            [1, $this->getEntity(Localization::class, ['id' => 1, 'name' => 'Localization 1'])],
            [2, $this->getEntity(Localization::class, ['id' => 2, 'name' => 'Localization 2'])],
            [3, $this->getEntity(Localization::class, ['id' => 3, 'name' => 'Localization 3'])],
        ];
        $this->localizationProvider->method('getLocalization')
            ->will($this->returnValueMap($map));

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

        parent::setUp();
    }

    public function tearDown()
    {
        unset(
            $this->configManager,
            $this->localeSettings,
            $this->localizationProvider,
            $this->localizationChoicesProvider,
            $this->translator,
            $this->requestStack
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_locale_default_localization_selection' => new DefaultLocalizationSelectionType(
                        $this->configManager,
                        $this->localeSettings,
                        $this->localizationProvider,
                        $this->localizationChoicesProvider,
                        $this->translator,
                        $this->requestStack
                    ),
                ],
                []
            ),
        ];
    }

    public function testGetName()
    {
        $formType = new DefaultLocalizationSelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->localizationProvider,
            $this->localizationChoicesProvider,
            $this->translator,
            $this->requestStack
        );
        $this->assertEquals(DefaultLocalizationSelectionType::NAME, $formType->getName());
    }

    /**
     * @dataProvider submitFormDataProvider
     *
     * @param array $defaultLocalization
     * @param array $enabledLocalizations
     * @param string $submittedValue
     * @param bool $isValid
     */
    public function testSubmitValidForm(
        array $defaultLocalization,
        array $enabledLocalizations,
        $submittedValue,
        $isValid
    ) {
        $currentRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $currentRequest->expects($this->once())
            ->method('get')
            ->with('localization')
            ->willReturn([
                DefaultLocalizationSelectionType::DEFAULT_LOCALIZATION_NAME => $defaultLocalization,
                DefaultLocalizationSelectionType::ENABLED_LOCALIZATIONS_NAME => $enabledLocalizations
            ]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($currentRequest);

        $form = $this->factory->create('oro_locale_default_localization_selection');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm */
        $rootForm = $this->getMock(FormInterface::class);

        $rootForm->expects($this->once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $rootForm->expects($this->once())
            ->method('getName')
            ->willReturn('localization');

        $rootForm->expects($this->once())
            ->method('has')
            ->with(DefaultLocalizationSelectionType::ENABLED_LOCALIZATIONS_NAME)
            ->willReturn(true);

        $form->setParent($rootForm);

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
                'defaultLocalization' => [
                    'value' => '1'
                ],
                'enabledLocalizations' => [
                    'value' => ['1', '2', '3']
                ],
                'submittedValue' => '1',
                'isValid' => true
            ],
            'invalid without default' => [
                'defaultLocalization' => [
                    'value' => '1'
                ],
                'enabledLocalizations' => [
                    'value' => ['2']
                ],
                'submittedValue' => '1',
                'isValid' => false
            ],
            'valid with enabledLocalizations default' => [
                'defaultLocalization' => [
                    'value' => '1'
                ],
                'enabledLocalizations' => [
                    'use_parent_scope_value' => true,
                ],
                'submittedValue' => '1',
                'isValid' => true
            ]
        ];
    }
}
