<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormStartType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormView;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmbedFormStartTypeTest extends BlockTypeTestCase
{
    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(EmbedFormStartType::NAME, []);
        $this->assertEquals('form', $options['form_name']);
    }

    public function testGetBlockView()
    {
        $formName = 'test_form';
        $formActionPath = 'test_form_action_path';
        $formMethod = 'GET';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->once())
            ->method('getAction')
            ->willReturn(FormAction::createByPath($formActionPath));
        $formAccessor->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);
        $formAccessor->expects($this->once())
            ->method('getEnctype')
            ->willReturn($formEnctype);

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(EmbedFormStartType::NAME, ['form_name' => $formName]);

        $this->assertSame($formView, $view->vars['form']);
        $this->assertSame($formActionPath, $view->vars['action_path']);
        $this->assertArrayNotHasKey('action_route_name', $view->vars);
        $this->assertArrayNotHasKey('action_route_parameters', $view->vars);
        $this->assertSame($formMethod, $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithRoute()
    {
        $formName = 'test_form';
        $formActionRoute = 'test_form_action_route';
        $formActionRouteParams = ['foo' => 'bar'];
        $formMethod = 'POST';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->once())
            ->method('getAction')
            ->willReturn(FormAction::createByRoute($formActionRoute, $formActionRouteParams));
        $formAccessor->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);
        $formAccessor->expects($this->once())
            ->method('getEnctype')
            ->willReturn($formEnctype);

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(EmbedFormStartType::NAME, ['form_name' => $formName]);

        $this->assertSame($formView, $view->vars['form']);
        $this->assertArrayNotHasKey('action_path', $view->vars);
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame($formActionRouteParams, $view->vars['action_route_parameters']);
        $this->assertSame($formMethod, $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithRouteWithoutParams()
    {
        $formName = 'test_form';
        $formActionRoute = 'test_form_action_route';
        $formMethod = 'POST';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->once())
            ->method('getAction')
            ->willReturn(FormAction::createByRoute($formActionRoute));
        $formAccessor->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);
        $formAccessor->expects($this->once())
            ->method('getEnctype')
            ->willReturn($formEnctype);

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(EmbedFormStartType::NAME, ['form_name' => $formName]);

        $this->assertSame($formView, $view->vars['form']);
        $this->assertArrayNotHasKey('action_path', $view->vars);
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame([], $view->vars['action_route_parameters']);
        $this->assertSame($formMethod, $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithEmptyFormParams()
    {
        $formName = 'test_form';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->once())
            ->method('getAction')
            ->willReturn(FormAction::createEmpty());
        $formAccessor->expects($this->once())
            ->method('getMethod')
            ->willReturn(null);
        $formAccessor->expects($this->once())
            ->method('getEnctype')
            ->willReturn(null);

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(EmbedFormStartType::NAME, ['form_name' => $formName]);

        $this->assertSame($formView, $view->vars['form']);
        $this->assertArrayNotHasKey('action_path', $view->vars);
        $this->assertArrayNotHasKey('action_route_name', $view->vars);
        $this->assertArrayNotHasKey('action_route_parameters', $view->vars);
        $this->assertArrayNotHasKey('method', $view->vars);
        $this->assertArrayNotHasKey('enctype', $view->vars);
    }

    public function testGetBlockViewWithOverrideOptions()
    {
        $formName = 'test_form';
        $formActionPath = 'test_form_action_path';
        $formMethod = 'get';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->never())
            ->method('getAction');
        $formAccessor->expects($this->never())
            ->method('getMethod');
        $formAccessor->expects($this->never())
            ->method('getEnctype');

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormStartType::NAME,
            [
                'form_name'    => $formName,
                'form_action'  => $formActionPath,
                'form_method'  => $formMethod,
                'form_enctype' => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertSame($formActionPath, $view->vars['action_path']);
        $this->assertArrayNotHasKey('action_route_name', $view->vars);
        $this->assertArrayNotHasKey('action_route_parameters', $view->vars);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithOverrideOptionsRoute()
    {
        $formName = 'test_form';
        $formActionRoute = 'test_form_action_route';
        $formActionRouteParams = ['foo' => 'bar'];
        $formMethod = 'get';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->never())
            ->method('getAction');
        $formAccessor->expects($this->never())
            ->method('getMethod');
        $formAccessor->expects($this->never())
            ->method('getEnctype');

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormStartType::NAME,
            [
                'form_name'             => $formName,
                'form_route_name'       => $formActionRoute,
                'form_route_parameters' => $formActionRouteParams,
                'form_method'           => $formMethod,
                'form_enctype'          => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertArrayNotHasKey('action_path', $view->vars);
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame($formActionRouteParams, $view->vars['action_route_parameters']);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithOverrideOptionsRouteWithoutParams()
    {
        $formName = 'test_form';
        $formActionRoute = 'test_form_action_route';
        $formMethod = 'get';
        $formEnctype = 'test_enctype';
        $formView = new FormView();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->willReturn($formView);
        $formAccessor->expects($this->never())
            ->method('getAction');
        $formAccessor->expects($this->never())
            ->method('getMethod');
        $formAccessor->expects($this->never())
            ->method('getEnctype');

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormStartType::NAME,
            [
                'form_name'       => $formName,
                'form_route_name' => $formActionRoute,
                'form_method'     => $formMethod,
                'form_enctype'    => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertArrayNotHasKey('action_path', $view->vars);
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame([], $view->vars['action_route_parameters']);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testGetBlockViewWithoutForm()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined index: test_form.');

        $this->getBlockView(
            EmbedFormStartType::NAME,
            ['form_name' => 'test_form']
        );
    }

    public function testGetBlockViewWithInvalidForm()
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
            EmbedFormStartType::NAME,
            ['form_name' => $formName]
        );
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(EmbedFormStartType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
