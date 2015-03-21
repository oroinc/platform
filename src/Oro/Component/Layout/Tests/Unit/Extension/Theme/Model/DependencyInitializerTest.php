<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Symfony\Component\DependencyInjection\Container;

use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;

class DependencyInitializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Container */
    protected $container;

    /** @var DependencyInitializer */
    protected $initializer;

    protected function setUp()
    {
        $this->container = new Container();
        $this->initializer = new DependencyInitializer($this->container);
    }

    protected function tearDown()
    {
        unset($this->initializer, $this->container);
    }

    public function testShouldNotFailWithNonObject()
    {
        $this->initializer->initialize(null);
    }

    public function testNoKnownDependenciesShouldNotDoAnything()
    {
        $object = $this->getMock(
            'Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ExpressionFactoryLayoutUpdateInterface'
        );
        $object->expects($this->never())->method('setExpressionFactory');

        $this->initializer->initialize($object);
    }

    public function testShouldInitializeDependencies()
    {
        $expressionFactory = $this->getMock('Oro\Component\ConfigExpression\ExpressionFactoryInterface');

        $object = $this->getMock(
            'Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ExpressionFactoryLayoutUpdateInterface'
        );
        $object->expects($this->once())->method('setExpressionFactory')->with($this->equalTo($expressionFactory));

        $this->container->set('factory_service_id', $expressionFactory);

        $this->initializer->addKnownDependency(
            '\Oro\Component\ConfigExpression\ExpressionFactoryAwareInterface',
            'setExpressionFactory',
            'factory_service_id'
        );

        $this->initializer->initialize($object);
    }
}
