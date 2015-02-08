<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeHierarchyRegistry;

class BlockTypeHierarchyRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var BlockTypeHierarchyRegistry */
    protected $typeHierarchyRegistry;

    protected function setUp()
    {
        $this->registry              = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->typeHierarchyRegistry = new BlockTypeHierarchyRegistry($this->registry);
    }

    public function testByBlockName()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->registry->expects($this->at(0))
            ->method('getType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->registry->expects($this->at(1))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->registry->expects($this->exactly(2))
            ->method('getType');

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->typeHierarchyRegistry->getTypes(ContainerType::NAME)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->typeHierarchyRegistry->getTypeNames(ContainerType::NAME)
        );
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf(ContainerType::NAME, ContainerType::NAME));
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf(ContainerType::NAME, BaseType::NAME));
        $this->assertFalse($this->typeHierarchyRegistry->isInstanceOf(ContainerType::NAME, 'another'));
    }

    public function testByAlreadyCreatedBlockTypeObject()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->typeHierarchyRegistry->getTypes($containerBlockType)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->typeHierarchyRegistry->getTypeNames($containerBlockType)
        );
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf($containerBlockType, ContainerType::NAME));
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf($containerBlockType, BaseType::NAME));
        $this->assertFalse($this->typeHierarchyRegistry->isInstanceOf($containerBlockType, 'another'));
    }

    public function testForBaseTypeByBlockName()
    {
        $type = new BaseType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($type));

        $this->assertSame(
            [$type],
            $this->typeHierarchyRegistry->getTypes(BaseType::NAME)
        );
        $this->assertSame(
            [$type->getName()],
            $this->typeHierarchyRegistry->getTypeNames(BaseType::NAME)
        );
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf(BaseType::NAME, BaseType::NAME));
        $this->assertFalse($this->typeHierarchyRegistry->isInstanceOf(BaseType::NAME, 'another'));
    }

    public function testForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $type = new BaseType();

        $this->registry->expects($this->never())
            ->method('getType');

        $this->assertSame(
            [$type],
            $this->typeHierarchyRegistry->getTypes($type)
        );
        $this->assertSame(
            [$type->getName()],
            $this->typeHierarchyRegistry->getTypeNames($type)
        );
        $this->assertTrue($this->typeHierarchyRegistry->isInstanceOf($type, BaseType::NAME));
        $this->assertFalse($this->typeHierarchyRegistry->isInstanceOf($type, 'another'));
    }
}
