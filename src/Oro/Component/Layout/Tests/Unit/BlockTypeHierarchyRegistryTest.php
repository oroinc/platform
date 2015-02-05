<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeChainRegistry;

class BlockTypeChainRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extensionManager;

    /** @var BlockTypeChainRegistry */
    protected $blockTypeChainRegistry;

    protected function setUp()
    {
        $this->extensionManager       = $this->getMock('Oro\Component\Layout\ExtensionManagerInterface');
        $this->blockTypeChainRegistry = new BlockTypeChainRegistry($this->extensionManager);
    }

    public function testGetBlockTypeChainByBlockName()
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
            $this->blockTypeChainRegistry->getBlockTypeChain(ContainerType::NAME)
        );
        // check that the chain is cached
        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->blockTypeChainRegistry->getBlockTypeChain(ContainerType::NAME)
        );
    }

    public function testGetBlockTypeChainByAlreadyCreatedBlockTypeObject()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->extensionManager->expects($this->once())
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->blockTypeChainRegistry->getBlockTypeChain($containerBlockType)
        );
        // check that the chain is cached
        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->blockTypeChainRegistry->getBlockTypeChain($containerBlockType)
        );
    }

    public function testGetBlockTypeChainForBaseTypeByBlockName()
    {
        $blockType = new BaseType();

        $this->extensionManager->expects($this->once())
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($blockType));

        $this->assertSame(
            [$blockType],
            $this->blockTypeChainRegistry->getBlockTypeChain(BaseType::NAME)
        );
        // check that the chain is cached
        $this->assertSame(
            [$blockType],
            $this->blockTypeChainRegistry->getBlockTypeChain(BaseType::NAME)
        );
    }

    public function testGetBlockTypeChainForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $blockType = new BaseType();

        $this->extensionManager->expects($this->never())
            ->method('getBlockType');

        $this->assertSame(
            [$blockType],
            $this->blockTypeChainRegistry->getBlockTypeChain($blockType)
        );
        // check that the chain is cached
        $this->assertSame(
            [$blockType],
            $this->blockTypeChainRegistry->getBlockTypeChain($blockType)
        );
    }
}
