<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\TypeTestCase;

class FormFieldTypeTest extends TypeTestCase
{
    const TEST_LABEL = 'label';

    /**
     * @dataProvider buildFormOptionsProvider
     *
     * @param array  $options
     * @param string $expectedType
     * @param array  $expectedOptions
     */
    public function testBuildForm($options, $expectedType, array $expectedOptions)
    {
        $form = $this->factory->create(FormFieldType::class, array(), $options);

        $this->assertTrue($form->has('value'));
        $this->assertTrue($form->has('use_parent_scope_value'));

        $this->assertEquals($expectedType, get_class($form->get('value')->getConfig()->getType()->getInnerType()));

        foreach ($expectedOptions as $option => $value) {
            $this->assertEquals($value, $form->get('value')->getConfig()->getOption($option));
        }
    }

    /**
     * @return array
     */
    public function buildFormOptionsProvider()
    {
        return array(
            'target field options empty'                => array(
                'options'         => array(),
                'expectedType'    => TextType::class,
                'expectedOptions' => array()
            ),
            'target field options from array'           => array(
                'options'         => array(
                    'target_field_type'    => ChoiceType::class,
                    'target_field_options' => array('label' => self::TEST_LABEL)
                ),
                'expectedType'    => ChoiceType::class,
                'expectedOptions' => array('label' => self::TEST_LABEL)
            ),
        );
    }

    public function testGetName()
    {
        $formType = new FormFieldType();
        $this->assertEquals('oro_config_form_field_type', $formType->getName());
    }

    public function listenersDataProvider()
    {
        return [
            'resettable' => [true, 1],
            'non-resettable' => [false, 0],
        ];
    }

    /**
     * @dataProvider listenersDataProvider
     *
     * @param bool $resettable
     * @param int $expectedCount Expected invocation count
     */
    public function testListeners($resettable, $expectedCount)
    {
        /* @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $fieldBuilder = $this->createMock(FormBuilderInterface::class);

        $fieldBuilder->expects($this->exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT);

        $builder->expects($this->exactly($expectedCount))
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($fieldBuilder);

        $builder->expects($this->exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA);

        $formType = new FormFieldType();
        $formType->buildForm(
            $builder,
            [
                'parent_checkbox_label' => '',
                'resettable' => $resettable,
                'target_field_type' => 'array',
                'target_field_options' => [],
                'use_parent_field_options' => [],
            ]
        );
    }
}
