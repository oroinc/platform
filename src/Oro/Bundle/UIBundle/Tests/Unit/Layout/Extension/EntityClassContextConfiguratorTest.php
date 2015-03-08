<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\EntityClassContextConfigurator;

class EntityClassContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityClassContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new EntityClassContextConfigurator();
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $context['entity_class'] = 'Test\Class';
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('Test\Class', $context['entity_class']);
    }

    public function testEntityClassIsOptional()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse(isset($context['entity_class']));
    }

    public function testEntityClassIsSetBasedOnEntityData()
    {
        $context = new LayoutContext();

        $entity = new \stdClass();
        $context->data()->set('entity', 'id', $entity);
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals(get_class($entity), $context['entity_class']);
    }

    public function testEntityClassIsNotOverridden()
    {
        $context = new LayoutContext();

        $entity = new \stdClass();
        $context->data()->set('entity', 'id', $entity);
        $context['entity_class'] = 'Test\Class';
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('Test\Class', $context['entity_class']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The option "entity_class" with value "123" is expected to be of type "string", "null"
     */
    // @codingStandardsIgnoreEnd
    public function testEntityClassShouldBeString()
    {
        $context = new LayoutContext();

        $context['entity_class'] = 123;
        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }
}
