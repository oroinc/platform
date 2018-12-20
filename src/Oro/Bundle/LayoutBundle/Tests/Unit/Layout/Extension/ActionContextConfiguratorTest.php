<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class ActionContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new ActionContextConfigurator();
    }

    public function testConfigureContextWithDefaultAction()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('', $context['action']);
    }

    public function testConfigureContext()
    {
        $action = 'index';

        $context           = new LayoutContext();
        $context['action'] = $action;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($action, $context['action']);
    }
}
