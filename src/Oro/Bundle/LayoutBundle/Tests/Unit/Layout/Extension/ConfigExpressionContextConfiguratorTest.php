<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\ConfigExpressionContextConfigurator;

class ConfigExpressionContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigExpressionContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new ConfigExpressionContextConfigurator();
    }

    public function testDefaultValuesAfterConfigureContext()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['expressions_evaluate']);
        $this->assertFalse(isset($context['expressions_encoding']));
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context['expressions_evaluate']);
        $this->assertEquals('json', $context['expressions_encoding']);
    }
}
