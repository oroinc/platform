<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormEndType;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Symfony\Component\Form\FormView;

class EmbedFormEndTypeTest extends BlockTypeTestCase
{
    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(EmbedFormEndType::NAME, []);
        $this->assertEquals('form', $options['form_name']);
    }

    public function testGetBlockView()
    {
        $formName = 'test_form';
        $formView = new FormView();

        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormEndType::NAME,
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
            EmbedFormEndType::NAME,
            ['form_name' => 'test_form']
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testGetBlockViewViewWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, 123);
        $this->getBlockView(
            EmbedFormEndType::NAME,
            ['form_name' => $formName]
        );
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(EmbedFormEndType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
