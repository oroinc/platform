<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CheckboxType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\OroRichTextTypeStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\TextTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class FallbackValueTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new FallbackPropertyType($this->createMock(TranslatorInterface::class)),
                    TextType::class => new TextTypeStub(),
                    OroRichTextType::class => new OroRichTextTypeStub()
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        mixed $defaultData,
        array $viewData,
        ?array $submittedData,
        mixed $expectedData,
        array $expectedOptions
    ) {
        $form = $this->factory->create(FallbackValueType::class, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));
        $this->assertEquals(FallbackPropertyType::class, $formConfig->getOption('fallback_type'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'percent with value' => [
                'options' => [
                    'entry_type'    => PercentType::class,
                    'entry_options' => ['type' => 'integer'],
                    'group_fallback_fields' => null
                ],
                'defaultData'   => 25,
                'viewData'      => ['value' => 25, 'use_fallback' => false, 'fallback' => null],
                'submittedData' => ['value' => '55', 'use_fallback' => false, 'fallback' => ''],
                'expectedData'  => 55,
                'expectedOptions' => ['group_fallback_fields' => false]
            ],
            'text with fallback' => [
                'options' => [
                    'entry_type'              => FallbackPropertyType::class,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION],
                    'group_fallback_fields' => false
                ],
                'defaultData'   => new FallbackType(FallbackType::SYSTEM),
                'viewData'      => ['value' => null, 'use_fallback' => true, 'fallback' => FallbackType::SYSTEM],
                'submittedData' => [
                    'value' => '',
                    'use_fallback' => true,
                    'fallback' => FallbackType::PARENT_LOCALIZATION
                ],
                'expectedData'  => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                'expectedOptions' => ['group_fallback_fields' => false]
            ],
            'integer as null' => [
                'options' => [
                    'entry_type' => IntegerType::class,
                    'group_fallback_fields' => true
                ],
                'defaultData'   => null,
                'viewData'      => ['value' => null, 'use_fallback' => false, 'fallback' => null],
                'submittedData' => null,
                'expectedData'  => null,
                'expectedOptions' => ['group_fallback_fields' => true]
            ],
            'richtext with fallback' => [
                'options' => [
                    'entry_type'              => OroRichTextType::class,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION],
                    'group_fallback_fields' => null
                ],
                'defaultData'   => new FallbackType(FallbackType::SYSTEM),
                'viewData'      => ['value' => null, 'use_fallback' => true, 'fallback' => FallbackType::SYSTEM],
                'submittedData' => [
                    'value' => '',
                    'use_fallback' => true,
                    'fallback' => FallbackType::PARENT_LOCALIZATION
                ],
                'expectedData'  => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                'expectedOptions' => ['group_fallback_fields' => true]
            ]
        ];
    }

    public function testBuildForm()
    {
        $type = 'form_text';
        $fallbackType = 'form_fallback';
        $fallbackTypeLocalization = 'fallback_localization';
        $fallbackTypeParentLocalization = 'fallback_parent_localization';
        $options = ['key' => 'value'];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['value', $type, array_merge($options, ['required' => false])],
                [
                    'use_fallback',
                    CheckboxType::class,
                    ['label' => 'oro.locale.fallback.use_fallback.label']
                ],
                [
                    'fallback',
                    $fallbackType,
                    [
                        'enabled_fallbacks' => [],
                        'localization' => $fallbackTypeLocalization,
                        'parent_localization' => $fallbackTypeParentLocalization,
                        'required' => false,
                        'label' => 'oro.locale.fallback.form.label',
                        'tooltip' => 'oro.locale.fallback.form.tooltip',
                        'use_tabs' => true
                    ]
                ]
            )
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViewTransformer')
            ->with(new FallbackValueTransformer())
            ->willReturnSelf();

        $formType = new FallbackValueType();
        $formType->buildForm(
            $builder,
            [
                'entry_type' => $type,
                'entry_options' => $options,
                'exclude_parent_localization' => false,
                'fallback_type' => $fallbackType,
                'enabled_fallbacks' => [],
                'fallback_type_localization' => $fallbackTypeLocalization,
                'fallback_type_parent_localization' => $fallbackTypeParentLocalization,
                'use_tabs' => true
            ]
        );
    }

    public function testFinishView()
    {
        $groupFallbackFields = 'test value';
        $excludeParentLocalization = true;

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['form', '_custom_block_prefix'];

        $formType = new FallbackValueType();
        $formType->finishView(
            $formView,
            $this->createMock(FormInterface::class),
            [
                'group_fallback_fields' => $groupFallbackFields,
                'exclude_parent_localization' => $excludeParentLocalization,
                'use_tabs' => true
            ]
        );

        $this->assertArrayHasKey('group_fallback_fields', $formView->vars);
        $this->assertEquals($groupFallbackFields, $formView->vars['group_fallback_fields']);
        $this->assertArrayHasKey('exclude_parent_localization', $formView->vars);
        $this->assertEquals($excludeParentLocalization, $formView->vars['exclude_parent_localization']);
        $this->assertEquals(
            ['form', 'oro_locale_fallback_value_tabs', '_custom_block_prefix'],
            $formView->vars['block_prefixes']
        );
    }

    public function testGetName()
    {
        $formType = new FallbackValueType();
        $this->assertEquals(FallbackValueType::NAME, $formType->getName());
    }
}
