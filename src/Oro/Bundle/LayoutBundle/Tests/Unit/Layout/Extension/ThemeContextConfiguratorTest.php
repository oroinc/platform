<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeContextConfigurator;

class ThemeContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new ThemeContextConfigurator();
    }

    public function testConfigureContextWithOutRequest()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get('theme'));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_theme', 'testTheme');

        $this->contextConfigurator->setRequest($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testTheme', $context->get('theme'));
    }

    public function testConfigureContextWithRequestAndDataSetInContext()
    {
        $context = new LayoutContext();
        $context->set('theme', 'themeShouldNotBeOverridden');

        $request = Request::create('');
        $request->attributes->set('_theme', 'testTheme');

        $this->contextConfigurator->setRequest($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('themeShouldNotBeOverridden', $context->get('theme'));
    }

    public function testRequestSetterSynchronized()
    {
        $this->contextConfigurator->setRequest(new Request());
        $this->contextConfigurator->setRequest(null);
    }
}
