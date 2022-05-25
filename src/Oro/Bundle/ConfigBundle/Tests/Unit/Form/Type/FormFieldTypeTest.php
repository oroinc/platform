<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class FormFieldTypeTest extends FormIntegrationTestCase
{
    private const TEST_LABEL = 'label';

    /**
     * @dataProvider buildFormOptionsProvider
     */
    public function testBuildForm(array $options, string $expectedType, array $expectedOptions): void
    {
        $form = $this->factory->create(FormFieldType::class, [], $options);

        self::assertTrue($form->has('value'));
        self::assertTrue($form->has('use_parent_scope_value'));

        self::assertEquals($expectedType, get_class($form->get('value')->getConfig()->getType()->getInnerType()));

        foreach ($expectedOptions as $option => $value) {
            self::assertEquals($value, $form->get('value')->getConfig()->getOption($option));
        }
    }

    public function buildFormOptionsProvider(): array
    {
        return [
            'target field options empty' => [
                'options' => [],
                'expectedType' => TextType::class,
                'expectedOptions' => [],
            ],
            'target field options from array' => [
                'options' => [
                    'target_field_type' => ChoiceType::class,
                    'target_field_options' => ['label' => self::TEST_LABEL],
                ],
                'expectedType' => ChoiceType::class,
                'expectedOptions' => ['label' => self::TEST_LABEL],
            ],
        ];
    }

    public function listenersDataProvider(): array
    {
        return [
            'resettable' => [true, 1],
            'non-resettable' => [false, 0],
        ];
    }

    /**
     * @dataProvider listenersDataProvider
     */
    public function testListeners(bool $resettable, int $expectedCount): void
    {
        /* @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $fieldBuilder = $this->createMock(FormBuilderInterface::class);

        $fieldBuilder->expects(self::exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT);

        $builder->expects(self::exactly($expectedCount))
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($fieldBuilder);

        $builder->expects(self::exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA);

        $formType = new FormFieldType();
        $formType->buildForm(
            $builder,
            [
                'target_field_type' => 'array',
                'target_field_options' => [],
                'resettable' => $resettable,
                'use_parent_field_options' => [],
                'use_parent_field_label' => '',
            ]
        );
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $data,
        array $submitData,
        array $expected,
        bool $valueDisabled
    ): void {
        $form = $this->factory->create(FormFieldType::class, $data);

        $form->submit($submitData);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $form->getData());
        self::assertEquals(
            $valueDisabled,
            $form->get('value')->getConfig()->getOption('disabled', false)
        );
    }

    public function submitDataProvider(): array
    {
        return [
            'empty data' => [
                'data' => [],
                'submitData' => [],
                'expected' => ['use_parent_scope_value' => false, 'value' => null],
                'valueDisabled' => false,
            ],
            'with value provided' => [
                'data' => [],
                'submitData' => ['value' => 'sample_value', 'use_parent_scope_value' => false],
                'expected' => ['use_parent_scope_value' => false, 'value' => 'sample_value'],
                'valueDisabled' => false,
            ],
            'parent scope used' => [
                'data' => ['use_parent_scope_value' => true],
                'submitData' => ['value' => 'sample_value', 'use_parent_scope_value' => true],
                'expected' => ['use_parent_scope_value' => true],
                'valueDisabled' => true,
            ],
        ];
    }
}
