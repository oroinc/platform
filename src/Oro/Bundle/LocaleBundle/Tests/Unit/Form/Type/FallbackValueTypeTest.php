<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\OroRichTextTypeStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\TextTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

class FallbackValueTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface $translator */
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        return [
            new PreloadedExtension(
                [
                    new FallbackPropertyType($translator),
                    TextType::class => new TextTypeStub(),
                    OroRichTextType::class => new OroRichTextTypeStub()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param mixed $defaultData
     * @param array $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $expectedOptions
     */
    public function testSubmit(
        array $options,
        $defaultData,
        array $viewData,
        $submittedData,
        $expectedData,
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
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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
        $builder->expects($this->at(0))
            ->method('add')
            ->with('value', $type, array_merge($options, ['required' => false]))
            ->willReturnSelf();
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'use_fallback',
                CheckboxType::class,
                ['label' => 'oro.locale.fallback.use_fallback.label']
            )->willReturnSelf();
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'fallback',
                $fallbackType,
                [
                    'enabled_fallbacks' => [],
                    'localization' => $fallbackTypeLocalization,
                    'parent_localization' => $fallbackTypeParentLocalization,
                    'required' => false
                ]
            )->willReturnSelf();
        $builder->expects($this->at(3))
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
            ]
        );
    }

    public function testFinishView()
    {
        $groupFallbackFields = 'test value';
        $excludeParentLocalization = true;

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $formType = new FallbackValueType();
        $formType->finishView(
            $formView,
            $formMock,
            [
                'group_fallback_fields' => $groupFallbackFields,
                'exclude_parent_localization' => $excludeParentLocalization
            ]
        );

        $this->assertArrayHasKey('group_fallback_fields', $formView->vars);
        $this->assertEquals($groupFallbackFields, $formView->vars['group_fallback_fields']);
        $this->assertArrayHasKey('exclude_parent_localization', $formView->vars);
        $this->assertEquals($excludeParentLocalization, $formView->vars['exclude_parent_localization']);
    }

    public function testGetName()
    {
        $formType = new FallbackValueType();
        $this->assertEquals(FallbackValueType::NAME, $formType->getName());
    }
}
