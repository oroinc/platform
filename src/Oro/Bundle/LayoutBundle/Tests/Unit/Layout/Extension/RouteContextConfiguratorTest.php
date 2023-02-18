<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\RouteContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var RouteContextConfigurator */
    private $configurator;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->configurator = new RouteContextConfigurator($this->requestStack);
    }

    public function testConfigureContextWithOutRequest()
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get('route_name'));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'testRoteName');

        $this->requestStack->push($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testRoteName', $context->get('route_name'));
    }

    public function testConfigureContextWithSubRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_master_request_route', 'testRoteName');

        $this->requestStack->push($request);
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

        $this->requestStack->push($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('routeShouldNotBeOverridden', $context->get('route_name'));
    }
}
