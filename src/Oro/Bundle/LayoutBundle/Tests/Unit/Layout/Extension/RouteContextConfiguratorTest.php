<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\RouteContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteContextConfiguratorTest extends TestCase
{
    private RequestStack $requestStack;
    private RouteContextConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->configurator = new RouteContextConfigurator($this->requestStack);
    }

    public function testConfigureContextWithOutRequest(): void
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get('route_name'));
    }

    public function testConfigureContextWithRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'testRoteName');

        $this->requestStack->push($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testRoteName', $context->get('route_name'));
    }

    public function testConfigureContextWithSubRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_master_request_route', 'testRoteName');

        $this->requestStack->push($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testRoteName', $context->get('route_name'));
    }

    public function testConfigureContextWithRequestAndDataSetInContext(): void
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
