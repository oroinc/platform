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
            ->method('getBlockType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->extensionManager->expects($this->at(1))
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->extensionManager->expects($this->exactly(2))
            ->method('getBlockType');

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypes(ContainerType::NAME)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->registry->getBlockTypeNames(ContainerType::NAME)
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
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypes($containerBlockType)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->registry->getBlockTypeNames($containerBlockType)
        );
        $this->assertTrue($this->registry->isInstanceOf($containerBlockType, ContainerType::NAME));
        $this->assertTrue($this->registry->isInstanceOf($containerBlockType, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf($containerBlockType, 'another'));
    }

    public function testForBaseTypeByBlockName()
    {
        $blockType = new BaseType();

        $this->extensionManager->expects($this->once())
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($blockType));

        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypes(BaseType::NAME)
        );
        $this->assertSame(
            [$blockType->getName()],
            $this->registry->getBlockTypeNames(BaseType::NAME)
        );
        $this->assertTrue($this->registry->isInstanceOf(BaseType::NAME, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf(BaseType::NAME, 'another'));
    }

    public function testForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $blockType = new BaseType();

        $this->extensionManager->expects($this->never())
            ->method('getBlockType');

        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypes($blockType)
        );
        $this->assertSame(
            [$blockType->getName()],
            $this->registry->getBlockTypeNames($blockType)
        );
        $this->assertTrue($this->registry->isInstanceOf($blockType, BaseType::NAME));
        $this->assertFalse($this->registry->isInstanceOf($blockType, 'another'));
    }
}
