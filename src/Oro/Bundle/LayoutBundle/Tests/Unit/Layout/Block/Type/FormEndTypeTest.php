<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\Form\FormView;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormEndType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormEndTypeTest extends BlockTypeTestCase
{
    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(FormEndType::NAME, []);
        $this->assertEquals('form', $options['form_name']);
    }

    public function testGetBlockView()
    {
        $formName = 'test_form';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormEndType::NAME,
            ['form_name' => $formName, 'render_rest' => true]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertTrue($view->vars['render_rest']);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testGetBlockViewViewWithoutForm()
    {
        $this->getBlockView(
            FormEndType::NAME,
            ['form_name' => 'test_form']
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testGetBlockViewViewWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, 123);
        $this->getBlockView(
            FormEndType::NAME,
            ['form_name' => $formName]
        );
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormEndType::NAME);

        $this->assertSame(FormEndType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormEndType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
