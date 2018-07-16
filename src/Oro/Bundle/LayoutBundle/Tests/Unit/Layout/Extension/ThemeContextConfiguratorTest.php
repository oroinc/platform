<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemeContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeContextConfigurator */
    protected $contextConfigurator;

    /** @var RequestStack */
    protected $requestStack;

    protected function setUp()
    {
        $this->requestStack = new RequestStack();
        $this->contextConfigurator = new ThemeContextConfigurator($this->requestStack);
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

        $this->requestStack->push($request);
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

        $this->requestStack->push($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('themeShouldNotBeOverridden', $context->get('theme'));
    }

    public function testConfigureContextWithRequestDefaultTheme()
    {
        $context = new LayoutContext();

        $request = Request::create('');

        $this->requestStack->push($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('default', $context->get('theme'));
    }
}
