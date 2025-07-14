<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemeContextConfiguratorTest extends TestCase
{
    private RequestStack $requestStack;
    private ThemeContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->contextConfigurator = new ThemeContextConfigurator($this->requestStack);
    }

    public function testConfigureContextWithOutRequest(): void
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get('theme'));
    }

    public function testConfigureContextWithRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_theme', 'testTheme');

        $this->requestStack->push($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('testTheme', $context->get('theme'));
    }

    public function testConfigureContextWithRequestAndDataSetInContext(): void
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

    public function testConfigureContextWithRequestDefaultTheme(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');

        $this->requestStack->push($request);
        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        $this->assertSame('default', $context->get('theme'));
    }
}
