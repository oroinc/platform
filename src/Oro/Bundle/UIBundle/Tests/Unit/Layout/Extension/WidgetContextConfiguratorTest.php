<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\WidgetContextConfigurator;

class WidgetContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetContextConfigurator */
    protected $configurator;

    protected function setUp()
    {
        $this->configurator = new WidgetContextConfigurator();
    }

    protected function tearDown()
    {
        unset($this->configurator);
    }

    public function testRequestSetterSynchronized()
    {
        $this->configurator->setRequest(new Request());
        $this->configurator->setRequest(null);
    }

    public function testConfigureContextWithOutRequest()
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get(WidgetContextConfigurator::PARAM_WIDGET));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->request->set('_widgetContainer', 'testWidget');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testWidget', $context->get(WidgetContextConfigurator::PARAM_WIDGET));
    }

    public function testConfigureContextWitWidgetInQueryString()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->query->set('_widgetContainer', 'testWidget');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testWidget', $context->get(WidgetContextConfigurator::PARAM_WIDGET));
    }

    public function testConfigureContextWithRequestAndDataSetInContext()
    {
        $context = new LayoutContext();
        $context->set(WidgetContextConfigurator::PARAM_WIDGET, 'widgetShouldNotBeOverridden');

        $request = Request::create('');
        $request->attributes->set('_widgetContainer', 'testWidget');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('widgetShouldNotBeOverridden', $context->get(WidgetContextConfigurator::PARAM_WIDGET));
    }
}
