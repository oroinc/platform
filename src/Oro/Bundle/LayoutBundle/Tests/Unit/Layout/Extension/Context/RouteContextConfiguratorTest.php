<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Context;

use Oro\Bundle\LayoutBundle\Command\Util\DebugLayoutContext;
use Symfony\Component\HttpFoundation\Request;

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
        $context = new DebugLayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertContains('routeName', $context->getDataResolver()->getKnownOptions());
        $this->assertSame(null, $context->get(RouteContextConfigurator::PARAM_NAME));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new DebugLayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'testRoteName');

        $this->configurator->setRequest($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertContains('routeName', $context->getDataResolver()->getKnownOptions());
        $this->assertSame('testRoteName', $context->get(RouteContextConfigurator::PARAM_NAME));
    }

    public function testRequestSetterSynchronized()
    {
        $request = Request::create('');

        $this->configurator->setRequest($request);
        $this->configurator->setRequest(null);
    }
}
