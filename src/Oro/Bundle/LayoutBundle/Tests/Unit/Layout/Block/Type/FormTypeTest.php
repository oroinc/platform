<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormTypeTest extends BlockTypeTestCase
{
    /**
     * @dataProvider optionsDataProvider
     */
    public function testSetDefaultOptions($options, $expected)
    {
        $resolvedOptions = $this->resolveOptions(FormType::NAME, $options);
        $this->assertEquals($expected, $resolvedOptions);
    }

    public function optionsDataProvider()
    {
        return [
            'no options'     => [
                'options'  => [],
                'expected' => [
                    'form_name'         => 'form',
                    'preferred_fields'  => [],
                    'groups'            => [],
                    'form_field_prefix' => 'form_',
                    'form_group_prefix' => 'form:group_'
                ]
            ],
            'with form_name' => [
                'options'  => [
                    'form_name' => 'test'
                ],
                'expected' => [
                    'form_name'         => 'test',
                    'preferred_fields'  => [],
                    'groups'            => [],
                    'form_field_prefix' => 'test_',
                    'form_group_prefix' => 'test:group_'
                ]
            ],
            'all options'    => [
                'options'  => [
                    'form_name'         => 'test',
                    'preferred_fields'  => ['field1'],
                    'groups'            => ['group1' => ['title' => 'TestGroup']],
                    'form_field_prefix' => 'form_field_prefix_',
                    'form_group_prefix' => 'form_group_prefix_'
                ],
                'expected' => [
                    'form_name'         => 'test',
                    'preferred_fields'  => ['field1'],
                    'groups'            => ['group1' => ['title' => 'TestGroup']],
                    'form_field_prefix' => 'form_field_prefix_',
                    'form_group_prefix' => 'form_group_prefix_'
                ]
            ]
        ];
    }

    public function testBuildBlockWithForm()
    {
        $formName = 'test_form';

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->context->set($formName, $form);

        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $formLayoutBuilder = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilderInterface');

        $type    = new FormType($formLayoutBuilder);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => $formName]
        );

        $formLayoutBuilder->expects($this->once())
            ->method('build')
            ->with($this->identicalTo($form), $this->identicalTo($builder), $options);

        $type->buildBlock($builder, $options);

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor',
            $this->context->get($formName)
        );
    }

    public function testBuildBlockWithFormAccessor()
    {
        $formName = 'test_form';

        $form         = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->context->set($formName, $formAccessor);

        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $formLayoutBuilder = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilderInterface');

        $type    = new FormType($formLayoutBuilder);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => $formName]
        );

        $formLayoutBuilder->expects($this->once())
            ->method('build')
            ->with($this->identicalTo($form), $this->identicalTo($builder), $options);

        $type->buildBlock($builder, $options);

        $this->assertSame(
            $formAccessor,
            $this->context->get($formName)
        );
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testBuildBlockWithoutForm()
    {
        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $type    = $this->getBlockType(FormType::NAME);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => 'test_form']
        );
        $type->buildBlock($builder, $options);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Symfony\Component\Form\FormInterface or Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testBuildBlockWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->set($formName, 123);

        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $type    = $this->getBlockType(FormType::NAME);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => $formName]
        );
        $type->buildBlock($builder, $options);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormType::NAME);

        $this->assertSame(FormType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
