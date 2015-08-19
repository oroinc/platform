<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\RouteContextConfigurator;

class RouteContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RouteContextConfigurator */
    protected $configurator;

    protected function setUp()
    {
        $this->configurator = new RouteContextConfigurator();
    }

    protected function tearDown()
    {
        unset($this->configurator);
    }

    public function testConfigureContextWithOutRequest()
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame(null, $context->get('route_name'));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'testRoteName');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testRoteName', $context->get('route_name'));
    }

    public function testConfigureContextWithSubRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_master_request_route', 'testRoteName');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testRoteName', $context->get('route_name'));
    }

    public function testConfigureContextWithRequestAndDataSetInContext()
    {
        $context = new LayoutContext();
        $context->set('route_name', 'routeShouldNotBeOverridden');

        $request = Request::create('');
        $request->attributes->set('_route', 'testRoteName');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('routeShouldNotBeOverridden', $context->get('route_name'));
    }

    public function testRequestSetterSynchronized()
    {
        $request = Request::create('');

        $this->configurator->setRequest($request);
        $this->configurator->setRequest(null);
    }
}
