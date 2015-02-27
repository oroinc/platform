<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer;

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
        $object = $this->getMock('\Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\ExpressionAssemblerLayoutUpdateInterface');
        $object->expects($this->never())->method('setAssembler');

        $this->initializer->initialize($object);
    }

    public function testShouldInitializeDependencies()
    {
        $assembler = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionAssembler')
            ->disableOriginalConstructor()->getMock();

        $object = $this->getMock('\Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\ExpressionAssemblerLayoutUpdateInterface');
        $object->expects($this->once())->method('setAssembler')->with($this->equalTo($assembler));

        $this->container->set('oro.assembler.service_id', $assembler);

        $this->initializer->addKnownDependency(
            '\Oro\Component\ConfigExpression\ExpressionAssemblerAwareInterface',
            'setAssembler',
            'oro.assembler.service_id'
        );

        $this->initializer->initialize($object);
    }
}
