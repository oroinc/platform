<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\ExpressionContextConfigurator;

class ExpressionContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new ExpressionContextConfigurator();
    }

    public function testDefaultValuesAfterConfigureContext()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['expressions_evaluate']);
        $this->assertFalse($context['expressions_evaluate_deferred']);
        $this->assertFalse(isset($context['expressions_encoding']));
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $context['expressions_evaluate'] = false;
        $context['expressions_evaluate_deferred'] = true;
        $context['expressions_encoding'] = 'json';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context['expressions_evaluate']);
        $this->assertTrue($context['expressions_evaluate_deferred']);
        $this->assertEquals('json', $context['expressions_encoding']);
    }
}
