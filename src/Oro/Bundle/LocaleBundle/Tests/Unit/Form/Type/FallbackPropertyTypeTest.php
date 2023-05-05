<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FallbackPropertyTypeTest extends FormIntegrationTestCase
{
    /** @var FallbackPropertyType */
    private $formType;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
                ['oro.locale.fallback.type.parent_localization', [], null, null, 'Parent Localization'],
                ['oro.locale.fallback.type.custom', [], null, null, 'Custom']
            ]);

        $this->formType = new FallbackPropertyType($translator);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, ?string $submittedData)
    {
        $form = $this->factory->create(FallbackPropertyType::class, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($submittedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'default options' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'required' => false,
                    'placeholder' => false,
                    'choices' => [
                        'oro.locale.fallback.type.default' => FallbackType::SYSTEM,
                    ],
                ],
                'submittedData' => FallbackType::SYSTEM,
            ],
            'parent localization' => [
                'inputOptions' => [
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION]
                ],
                'expectedOptions' => [
                    'required' => false,
                    'placeholder' => false,
                    'choices' => [
                        'oro.locale.fallback.type.parent_localization' => FallbackType::PARENT_LOCALIZATION,
                        'oro.locale.fallback.type.default' => FallbackType::SYSTEM,
                    ],
                ],
                'submittedData' => FallbackType::PARENT_LOCALIZATION,
            ],
            'parent localization with suffix' => [
                'inputOptions' => [
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION],
                    'localization' => 'en_US',
                    'parent_localization' => 'en',
                ],
                'expectedOptions' => [
                    'required' => false,
                    'placeholder' => false,
                    'choices' => [
                        'en [Parent Localization]' => FallbackType::PARENT_LOCALIZATION,
                        'oro.locale.fallback.type.default' => FallbackType::SYSTEM,
                    ],
                ],
                'submittedData' => FallbackType::PARENT_LOCALIZATION,
            ],
            'custom choices' => [
                'inputOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'expectedOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'submittedData' => null,
            ],
            'with tabs' => [
                'inputOptions' => [
                    'use_tabs' => true,
                ],
                'expectedOptions' => [
                    'choices' => [
                        'oro.locale.fallback.type.default' => FallbackType::SYSTEM,
                        'Custom' => FallbackType::NONE,
                    ],
                    'use_tabs' => true,
                ],
                'submittedData' => FallbackType::NONE,
            ],
        ];
    }

    public function testFinishView()
    {
        $localizationCode = 'en_US';
        $parentCode = 'en';

        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $this->formType->finishView($formView, $form, [
            'localization' => $localizationCode,
            'parent_localization' => $parentCode,
        ]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertArrayHasKey('data-localization', $formView->vars['attr']);
        $this->assertArrayHasKey('data-parent-localization', $formView->vars['attr']);
        $this->assertEquals($localizationCode, $formView->vars['attr']['data-localization']);
        $this->assertEquals($parentCode, $formView->vars['attr']['data-parent-localization']);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
