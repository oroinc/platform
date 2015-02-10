<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeHelper;

class BlockTypeHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var BlockTypeHelper */
    protected $typeHelper;

    protected function setUp()
    {
        $this->registry   = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->typeHelper = new BlockTypeHelper($this->registry);
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
            $this->typeHelper->getTypes(ContainerType::NAME)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->typeHelper->getTypeNames(ContainerType::NAME)
        );
        $this->assertTrue($this->typeHelper->isInstanceOf(ContainerType::NAME, ContainerType::NAME));
        $this->assertTrue($this->typeHelper->isInstanceOf(ContainerType::NAME, BaseType::NAME));
        $this->assertFalse($this->typeHelper->isInstanceOf(ContainerType::NAME, 'another'));
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
            $this->typeHelper->getTypes($containerBlockType)
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName()],
            $this->typeHelper->getTypeNames($containerBlockType)
        );
        $this->assertTrue($this->typeHelper->isInstanceOf($containerBlockType, ContainerType::NAME));
        $this->assertTrue($this->typeHelper->isInstanceOf($containerBlockType, BaseType::NAME));
        $this->assertFalse($this->typeHelper->isInstanceOf($containerBlockType, 'another'));
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
            $this->typeHelper->getTypes(BaseType::NAME)
        );
        $this->assertSame(
            [$type->getName()],
            $this->typeHelper->getTypeNames(BaseType::NAME)
        );
        $this->assertTrue($this->typeHelper->isInstanceOf(BaseType::NAME, BaseType::NAME));
        $this->assertFalse($this->typeHelper->isInstanceOf(BaseType::NAME, 'another'));
    }

    public function testForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $type = new BaseType();

        $this->registry->expects($this->never())
            ->method('getType');

        $this->assertSame(
            [$type],
            $this->typeHelper->getTypes($type)
        );
        $this->assertSame(
            [$type->getName()],
            $this->typeHelper->getTypeNames($type)
        );
        $this->assertTrue($this->typeHelper->isInstanceOf($type, BaseType::NAME));
        $this->assertFalse($this->typeHelper->isInstanceOf($type, 'another'));
    }
}
