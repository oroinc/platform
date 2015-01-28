<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeRegistry;

class BlockTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockTypeRegistry */
    protected $registry;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $blockTypeFactory;

    protected function setUp()
    {
        $this->blockTypeFactory = $this->getMock('Oro\Component\Layout\BlockTypeFactoryInterface');
        $this->registry         = new BlockTypeRegistry($this->blockTypeFactory);
    }

    public function testGetBlockType()
    {
        $blockType = new BaseType();

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($blockType));

        $this->assertSame($blockType, $this->registry->getBlockType(BaseType::NAME));
        // check that the created block type is cached
        $this->assertSame($blockType, $this->registry->getBlockType(BaseType::NAME));
    }

    public function testHasBlockType()
    {
        $blockType = new BaseType();

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($blockType));

        $this->assertTrue($this->registry->hasBlockType(BaseType::NAME));
        // check that the created block type is cached
        $this->assertTrue($this->registry->hasBlockType(BaseType::NAME));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type name must not be empty.
     */
    public function testGetTypeWithEmptyName($name)
    {
        $this->registry->getBlockType($name);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testHasTypeWithEmptyName($name)
    {
        $this->assertFalse($this->registry->hasBlockType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testGetTypeWithNotStringName()
    {
        $this->registry->getBlockType(1);
    }

    public function testHasTypeWithNotStringName()
    {
        $this->assertFalse($this->registry->hasBlockType(1));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type name does not match the name declared in the class implementing this type. Expected "widget", given "button".
     */
    // @codingStandardsIgnoreEnd
    public function testGetTypeWhenGivenNameDoesNotMatchNameDeclaredInClass()
    {
        $blockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($blockType));
        $blockType->expects($this->exactly(2))
            ->method('getName')
            ->will($this->returnValue('button'));

        $this->registry->getBlockType('widget');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type named "widget" was not found.
     */
    public function testGetUndefinedType()
    {
        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue(null));

        $this->registry->getBlockType('widget');
    }

    public function testGetBlockTypeChainByBlockName()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->blockTypeFactory->expects($this->at(0))
            ->method('createBlockType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->blockTypeFactory->expects($this->at(1))
            ->method('createBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->blockTypeFactory->expects($this->exactly(2))
            ->method('createBlockType');

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypeChain(ContainerType::NAME)
        );
        // check that the chain is cached
        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypeChain(ContainerType::NAME)
        );
    }

    public function testGetBlockTypeChainByAlreadyCreatedBlockTypeObject()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));

        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypeChain($containerBlockType)
        );
        // check that the chain is cached
        $this->assertSame(
            [$baseBlockType, $containerBlockType],
            $this->registry->getBlockTypeChain($containerBlockType)
        );
    }

    public function testGetBlockTypeChainForBaseTypeByBlockName()
    {
        $blockType = new BaseType();

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($blockType));

        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypeChain(BaseType::NAME)
        );
        // check that the chain is cached
        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypeChain(BaseType::NAME)
        );
    }

    public function testGetBlockTypeChainForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $blockType = new BaseType();

        $this->blockTypeFactory->expects($this->never())
            ->method('createBlockType');

        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypeChain($blockType)
        );
        // check that the chain is cached
        $this->assertSame(
            [$blockType],
            $this->registry->getBlockTypeChain($blockType)
        );
    }

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }
}
