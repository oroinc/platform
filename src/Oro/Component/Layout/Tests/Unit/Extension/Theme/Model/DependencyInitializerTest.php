<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Symfony\Component\DependencyInjection\Container;

class DependencyInitializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Container */
    protected $container;

    /** @var DependencyInitializer */
    protected $initializer;

    protected function setUp()
    {
        $this->container   = new Container();
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
        $object = $this->createMock(
            'Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\LayoutUpdateWithDependency'
        );
        $object->expects($this->never())
            ->method('setContainer');

        $this->initializer->initialize($object);
    }

    public function testShouldInitializeDependencies()
    {
        $dependency = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $object = $this->createMock(
            'Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\LayoutUpdateWithDependency'
        );
        $object->expects($this->once())
            ->method('setContainer')
            ->with($this->identicalTo($dependency));

        $this->container->set('dependency_service_id', $dependency);

        $this->initializer->addKnownDependency(
            '\Symfony\Component\DependencyInjection\ContainerAwareInterface',
            'setContainer',
            'dependency_service_id'
        );

        $this->initializer->initialize($object);
    }
}
