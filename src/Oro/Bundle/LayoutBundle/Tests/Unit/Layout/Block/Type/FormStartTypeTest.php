<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\Form\FormView;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormStartType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormStartTypeTest extends BlockTypeTestCase
{
    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(FormStartType::NAME, []);
        $this->assertEquals('form', $options['form_name']);
    }

    public function testBuildViewWithAllOptions()
    {
        $formName       = 'test_form';
        $formActionPath = 'test_form_action_path';
        $formMethod     = 'get';
        $formEnctype    = 'test_form';
        $formView       = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            [
                'form_name'                    => $formName,
                'form_action_path'             => $formActionPath,
                'form_action_route_name'       => 'route',
                'form_action_route_parameters' => ['foo' => 'bar'],
                'form_method'                  => $formMethod,
                'form_enctype'                 => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertSame($formActionPath, $view->vars['action_path']);
        $this->assertFalse(array_key_exists('action_route_name', $view->vars));
        $this->assertFalse(array_key_exists('action_route_parameters', $view->vars));
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testBuildViewWithRoute()
    {
        $formName              = 'test_form';
        $formActionRoute       = 'test_form_action_route';
        $formActionRouteParams = ['foo' => 'bar'];
        $formMethod            = 'get';
        $formEnctype           = 'test_form';
        $formView              = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            [
                'form_name'                    => $formName,
                'form_action_route_name'       => $formActionRoute,
                'form_action_route_parameters' => $formActionRouteParams,
                'form_method'                  => $formMethod,
                'form_enctype'                 => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse(array_key_exists('action_path', $view->vars));
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame($formActionRouteParams, $view->vars['action_route_parameters']);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testBuildViewWithRouteWithoutParams()
    {
        $formName        = 'test_form';
        $formActionRoute = 'test_form_action_route';
        $formMethod      = 'get';
        $formEnctype     = 'test_form';
        $formView        = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            [
                'form_name'              => $formName,
                'form_action_route_name' => $formActionRoute,
                'form_method'            => $formMethod,
                'form_enctype'           => $formEnctype
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse(array_key_exists('action_path', $view->vars));
        $this->assertSame($formActionRoute, $view->vars['action_route_name']);
        $this->assertSame([], $view->vars['action_route_parameters']);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame($formEnctype, $view->vars['enctype']);
    }

    public function testBuildViewWithEmptyOptions()
    {
        $formName = 'test_form';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->never())
            ->method('getForm');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            [
                'form_name'                    => $formName,
                'form_action_path'             => null,
                'form_action_route_name'       => null,
                'form_action_route_parameters' => null,
                'form_method'                  => null,
                'form_enctype'                 => null
            ]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse(array_key_exists('action_path', $view->vars));
        $this->assertFalse(array_key_exists('action_route_name', $view->vars));
        $this->assertFalse(array_key_exists('action_route_parameters', $view->vars));
        $this->assertFalse(array_key_exists('method', $view->vars));
        $this->assertFalse(array_key_exists('enctype', $view->vars));
    }

    public function testBuildViewWithDefaultSymfonyForm()
    {
        $formName       = 'test_form';
        $formActionPath = 'test_form_action_path';
        $formMethod     = 'get';
        $formView       = new FormView();
        $form           = $this->getMock('Symfony\Component\Form\FormInterface');
        $formConfig     = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $formView->vars['multipart'] = false;

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));
        $formAccessor->expects($this->any())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formActionPath));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            ['form_name' => $formName]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertSame($formActionPath, $view->vars['action_path']);
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertFalse(array_key_exists('enctype', $view->vars));
    }

    public function testBuildViewWithDefaultSymfonyMultipartForm()
    {
        $formName       = 'test_form';
        $formActionPath = 'test_form_action_path';
        $formMethod     = 'get';
        $formView       = new FormView();
        $form           = $this->getMock('Symfony\Component\Form\FormInterface');
        $formConfig     = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $formView->vars['multipart'] = true;

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));
        $formAccessor->expects($this->any())
            ->method('getView')
            ->with(null)
            ->will($this->returnValue($formView));
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formActionPath));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormStartType::NAME,
            ['form_name' => $formName]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertSame($formActionPath, $view->vars['action_path']);
        $this->assertFalse(array_key_exists('action_route_name', $view->vars));
        $this->assertFalse(array_key_exists('action_route_parameters', $view->vars));
        $this->assertSame(strtoupper($formMethod), $view->vars['method']);
        $this->assertSame('multipart/form-data', $view->vars['enctype']);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testBuildViewWithoutForm()
    {
        $this->getBlockView(
            FormStartType::NAME,
            ['form_name' => 'test_form']
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

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, 123);
        $this->getBlockView(
            FormStartType::NAME,
            ['form_name' => $formName]
        );
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormStartType::NAME);

        $this->assertSame(FormStartType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormStartType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
