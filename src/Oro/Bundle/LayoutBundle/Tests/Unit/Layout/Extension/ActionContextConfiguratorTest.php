<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\UIBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class ActionContextConfiguratorTest extends \PHPUnit_Framework_TestCase
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
