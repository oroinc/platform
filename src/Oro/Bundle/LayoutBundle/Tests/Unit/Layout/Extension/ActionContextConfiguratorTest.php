<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;

class ActionContextConfiguratorTest extends TestCase
{
    private ActionContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextConfigurator = new ActionContextConfigurator();
    }

    public function testConfigureContextWithDefaultAction(): void
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('', $context['action']);
    }

    public function testConfigureContext(): void
    {
        $action = 'index';

        $context = new LayoutContext();
        $context['action'] = $action;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($action, $context['action']);
    }
}
