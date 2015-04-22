<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Extension\FormContextConfigurator;

class FormContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var FormContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->contextConfigurator = new FormContextConfigurator($this->container);
    }

    public function testCreateDIFormAccessor()
    {
        $context = new LayoutContext();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with('form_service_id')
            ->will($this->returnValue($form));

        $context['form']         = 'form_service_id';
        $context['form_action']  = 'action';
        $context['form_method']  = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor',
            $context['form']
        );

        /** @var DependencyInjectionFormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('action', $formAccessor->getAction()->getPath());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateFormAccessor()
    {
        $context = new LayoutContext();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $context['form']         = $form;
        $context['form_action']  = 'action';
        $context['form_method']  = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor',
            $context['form']
        );

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('action', $formAccessor->getAction()->getPath());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateDIFormAccessorByRoute()
    {
        $context = new LayoutContext();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with('form_service_id')
            ->will($this->returnValue($form));

        $context['form']                  = 'form_service_id';
        $context['form_route_name']       = 'route';
        $context['form_route_parameters'] = ['foo' => 'bar'];
        $context['form_method']           = 'method';
        $context['form_enctype']          = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor',
            $context['form']
        );

        /** @var DependencyInjectionFormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('route', $formAccessor->getAction()->getRouteName());
        $this->assertEquals(['foo' => 'bar'], $formAccessor->getAction()->getRouteParameters());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateFormAccessorByRoute()
    {
        $context = new LayoutContext();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $context['form']                  = $form;
        $context['form_route_name']       = 'route';
        $context['form_route_parameters'] = ['foo' => 'bar'];
        $context['form_method']           = 'method';
        $context['form_enctype']          = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor',
            $context['form']
        );

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('route', $formAccessor->getAction()->getRouteName());
        $this->assertEquals(['foo' => 'bar'], $formAccessor->getAction()->getRouteParameters());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "form" must be a string, "Symfony\Component\Form\FormInterface" or "Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface", but "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionIfInvalidFormType()
    {
        $context = new LayoutContext();

        $context['form'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testFormIsOptional()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse(isset($context['form']));
    }

    public function testDoNothingIfFormAccessorIsAlreadySet()
    {
        $context = new LayoutContext();

        $formAccessor    = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $context['form'] = $formAccessor;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame($formAccessor, $context['form']);
    }

    public function testCreateFormAccessorByFormActionObject()
    {
        $context = new LayoutContext();

        $form       = $this->getMock('Symfony\Component\Form\FormInterface');
        $formAction = FormAction::createEmpty();

        $context['form']         = $form;
        $context['form_action']  = $formAction;
        $context['form_method']  = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor',
            $context['form']
        );

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertSame($formAction, $formAccessor->getAction());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "form_action" must be a string or instance of "Oro\Bundle\LayoutBundle\Layout\Form\FormAction", but "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionIfInvalidFormAction()
    {
        $context = new LayoutContext();

        $context['form']        = $this->getMock('Symfony\Component\Form\FormInterface');
        $context['form_action'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "form_route_name" must be a string, but "integer" given.
     */
    public function testShouldThrowExceptionIfInvalidFormRoute()
    {
        $context = new LayoutContext();

        $context['form']            = $this->getMock('Symfony\Component\Form\FormInterface');
        $context['form_route_name'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "form_method" must be a string, but "integer" given.
     */
    public function testShouldThrowExceptionIfInvalidFormMethod()
    {
        $context = new LayoutContext();

        $context['form']        = $this->getMock('Symfony\Component\Form\FormInterface');
        $context['form_method'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "form_enctype" must be a string, but "integer" given.
     */
    public function testShouldThrowExceptionIfInvalidFormEnctype()
    {
        $context = new LayoutContext();

        $context['form']         = $this->getMock('Symfony\Component\Form\FormInterface');
        $context['form_enctype'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }
}
