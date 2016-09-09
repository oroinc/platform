<?php

namespace ConfigBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;

class FormFieldTypeTest extends TypeTestCase
{
    const TEST_LABEL = 'label';

    /** @var FormFieldType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new FormFieldType();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formType);
    }

    protected function getExtensions()
    {
        $useParentScope = new ParentScopeCheckbox();
        return [
            new PreloadedExtension(
                array(
                    $useParentScope->getName() => $useParentScope
                ),
                array()
            )
        ];
    }


    /**
     * @dataProvider buildFormOptionsProvider
     *
     * @param array  $options
     * @param string $expectedType
     * @param array  $expectedOptions
     */
    public function testBuildForm($options, $expectedType, array $expectedOptions)
    {
        $form = $this->factory->create($this->formType, array(), $options);

        $this->assertTrue($form->has('value'));
        $this->assertTrue($form->has('use_parent_scope_value'));

        $this->assertEquals($expectedType, $form->get('value')->getConfig()->getType()->getName());

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
                'expectedType'    => 'text',
                'expectedOptions' => array()
            ),
            'target field options from array'           => array(
                'options'         => array(
                    'target_field_type'    => 'choice',
                    'target_field_options' => array('label' => self::TEST_LABEL)
                ),
                'expectedType'    => 'choice',
                'expectedOptions' => array('label' => self::TEST_LABEL)
            ),
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_config_form_field_type', $this->formType->getName());
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
        /* @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $fieldBuilder = $this->getMock(FormBuilderInterface::class);

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

        $this->formType->buildForm(
            $builder,
            [
                'parent_checkbox_label' => '',
                'resettable' => $resettable,
                'target_field_type' => 'array',
                'target_field_options' => [],
            ]
        );
    }
}
