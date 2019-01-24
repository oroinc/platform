<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationSelectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var LocalizationChoicesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationChoicesProvider;

    /** @var LocalizationSelectionType */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->localizationManager->expects($this->any())
            ->method('getLocalizations')
            ->willReturnCallback(
                function (array $ids) {
                    $result = [];

                    foreach ($ids as $id) {
                        $result[$id] = $this->getEntity(Localization::class, ['id' => $id]);
                    }

                    return $result;
                }
            );

        $this->localizationChoicesProvider = $this->createMock(LocalizationChoicesProvider::class);
        $this->localizationChoicesProvider->expects($this->any())
            ->method('getLocalizationChoices')
            ->willReturn([
                'Localization 1' => 1001,
                'Localization 2' => 2002,
                'Localization 3' => 3003,
            ]);

        $this->formType = new LocalizationSelectionType(
            $this->localizationManager,
            $this->localizationChoicesProvider
        );

        parent::setUp();
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_locale_localization_selection', $this->formType->getName());
    }

    public function testGetParent(): void
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testConfigureOptions(): void
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => [
                    'Localization 1' => 1001,
                    'Localization 2' => 2002,
                    'Localization 3' => 3003,
                ],
                'placeholder' => '',
                'translatable_options' => false,
                'configs' => [
                    'placeholder' => 'oro.locale.localization.form.placeholder.select_localization',
                ],
                Configuration::ENABLED_LOCALIZATIONS => null
            ]);

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider submitFormDataProvider
     *
     * @param string $submittedValue
     * @param bool $isValid
     */
    public function testSubmitValidForm($submittedValue, $isValid): void
    {
        $form = $this->factory->create(LocalizationSelectionType::class);
        $form->submit($submittedValue);

        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider(): array
    {
        return [
            'valid' => [
                'submittedValue' => '2002',
                'isValid' => true
            ],
            'invalid' => [
                'submittedValue' => '10',
                'isValid' => false
            ],
        ];
    }

    /**
     * @dataProvider finishViewProvider
     *
     * @param null|array $enabledLocalizations
     * @param array $expected
     */
    public function testFinishView(?array $enabledLocalizations, array $expected): void
    {
        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView(1001, 1001, 'Localization 1'),
            new ChoiceView(2002, 2002, 'Localization 2'),
            new ChoiceView(3003, 3003, 'Localization 3'),
            new ChoiceView(4004, 4004, 'Localization 4'),
        ];

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView(
            $view,
            $form,
            [Configuration::ENABLED_LOCALIZATIONS => $enabledLocalizations]
        );

        $this->assertCount(count($expected), $view->vars['choices']);

        foreach ($expected as $key => $data) {
            $this->assertEquals($data['label'], $view->vars['choices'][$key]->label);
            $this->assertEquals($data['value'], $view->vars['choices'][$key]->value);
            $this->assertEquals($data['data'], $view->vars['choices'][$key]->data);
        }
    }

    /**
     * @return array
     */
    public function finishViewProvider(): array
    {
        return [
            'show all' => [
                'enabledLocalizations' => null,
                'expected' => [
                    0 => ['label' => 'Localization 1', 'value' => 1001, 'data' => 1001],
                    1 => ['label' => 'Localization 2', 'value' => 2002, 'data' => 2002],
                    2 => ['label' => 'Localization 3', 'value' => 3003, 'data' => 3003],
                ],
            ],
            'not show all' => [
                'enabledLocalizations' => [1001, 3003],
                'expected' => [
                    0 => ['label' => 'Localization 1', 'value' => 1001, 'data' => 1001],
                    2 => ['label' => 'Localization 3', 'value' => 3003, 'data' => 3003],
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $choiceType = $this->createMock(OroChoiceType::class);
        $choiceType->expects($this->any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

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
