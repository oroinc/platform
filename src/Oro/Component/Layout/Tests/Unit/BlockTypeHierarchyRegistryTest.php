<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeHierarchyRegistry;

class BlockTypeHierarchyRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extensionManager;

    /** @var BlockTypeHierarchyRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->extensionManager = $this->getMock('Oro\Component\Layout\ExtensionManagerInterface');
        $this->registry         = new BlockTypeHierarchyRegistry($this->extensionManager);
    }

    public function testByBlockName()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->extensionManager->expects($this->at(0))
            ->method('getType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->extensionManager->expects($this->at(1))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->extensionManager->expects($this->exactly(2))
            ->method('getType');

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getTypes(ContainerType::NAME)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->registry->getTypeNames(ContainerType::NAME)
        );
        $this->assertTrue($this->registry->isInstanceOf(ContainerType::NAME, ContainerType::NAME));
        $this->assertTrue($this->registry->isInstanceOf(ContainerType::NAME, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf(ContainerType::NAME, 'another'));
    }

    public function testByAlreadyCreatedBlockTypeObject()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->extensionManager->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getTypes($containerBlockType)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->registry->getTypeNames($containerBlockType)
        );
        $this->assertTrue($this->registry->isInstanceOf($containerBlockType, ContainerType::NAME));
        $this->assertTrue($this->registry->isInstanceOf($containerBlockType, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf($containerBlockType, 'another'));
    }

    public function testForBaseTypeByBlockName()
    {
        $type = new BaseType();

        $this->extensionManager->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($type));

        $this->assertSame(
            [$type],
            $this->registry->getTypes(BaseType::NAME)
        );
        $this->assertSame(
            [$type->getName()],
            $this->registry->getTypeNames(BaseType::NAME)
        );
        $this->assertTrue($this->registry->isInstanceOf(BaseType::NAME, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf(BaseType::NAME, 'another'));
    }

    public function testForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $type = new BaseType();

        $this->extensionManager->expects($this->never())
            ->method('getType');

        $this->assertSame(
            [$type],
            $this->registry->getTypes($type)
        );
        $this->assertSame(
            [$type->getName()],
            $this->registry->getTypeNames($type)
        );
        $this->assertTrue($this->registry->isInstanceOf($type, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf($type, 'another'));
    }
}
