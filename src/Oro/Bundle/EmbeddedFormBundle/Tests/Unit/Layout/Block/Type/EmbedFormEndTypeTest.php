<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormEndType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
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

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormEndType::NAME,
            ['form_name' => $formName, 'render_rest' => true]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertTrue($view->vars['render_rest']);
    }

    public function testGetBlockViewViewWithoutForm()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined index: test_form.');

        $this->getBlockView(
            EmbedFormEndType::NAME,
            ['form_name' => 'test_form']
        );
    }

    public function testGetBlockViewViewWithInvalidForm()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid "context[test_form]" argument type. Expected "%s", "integer" given.',
            FormAccessorInterface::class
        ));

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
