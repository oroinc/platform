<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Symfony\Component\Form\FormView;

class FormFieldTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $formName  = 'test_form';
        $formPath  = 'firstName';
        $fieldView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($fieldView));

        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath]
        );

        $this->assertSame($fieldView, $view->vars['form']);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testBuildViewWithoutForm()
    {
        $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => 'test_form', 'field_path' => 'firstName']
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testBuildViewWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->set($formName, 123);
        $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => 'firstName']
        );
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormFieldType::NAME);

        $this->assertSame(FormFieldType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormFieldType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
