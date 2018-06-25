<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockTypeHelper;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;

class BlockTypeHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var BlockTypeHelper */
    protected $typeHelper;

    protected function setUp()
    {
        $this->registry   = $this->createMock('Oro\Component\Layout\LayoutRegistryInterface');
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

    public function testWithAlreadyInitializedContainerParent()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();
        $headerBlockType    = new HeaderType();

        $this->registry->expects($this->at(0))
            ->method('getType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->registry->expects($this->at(1))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->registry->expects($this->at(2))
            ->method('getType')
            ->with($headerBlockType->getName())
            ->will($this->returnValue($headerBlockType));
        $this->registry->expects($this->exactly(3))
            ->method('getType');

        // get parent (here both 'block' and 'container' types are added to the local cache of the BlockTypeHelper)
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

        // get derived (here we test that the local cache of the BlockTypeHelper is used)
        $this->assertSame(
            [$baseBlockType, $containerBlockType, $headerBlockType],
            $this->typeHelper->getTypes($headerBlockType->getName())
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName(), $headerBlockType->getName()],
            $this->typeHelper->getTypeNames($headerBlockType->getName())
        );
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), $headerBlockType->getName()));
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), ContainerType::NAME));
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), BaseType::NAME));
    }

    public function testWithAlreadyInitializedBlockParent()
    {
        $baseBlockType = new BaseType();
        $logoBlockType = new LogoType();

        $this->registry->expects($this->at(0))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->registry->expects($this->at(1))
            ->method('getType')
            ->with($logoBlockType->getName())
            ->will($this->returnValue($logoBlockType));
        $this->registry->expects($this->exactly(2))
            ->method('getType');

        // get parent (here both 'block' and 'container' types are added to the local cache of the BlockTypeHelper)
        $this->assertSame(
            [$baseBlockType],
            $this->typeHelper->getTypes(BaseType::NAME)
        );
        $this->assertSame(
            [$baseBlockType->getName()],
            $this->typeHelper->getTypeNames(BaseType::NAME)
        );
        $this->assertTrue($this->typeHelper->isInstanceOf(BaseType::NAME, BaseType::NAME));

        // get derived (here we test that the local cache of the BlockTypeHelper is used)
        $this->assertSame(
            [$baseBlockType, $logoBlockType],
            $this->typeHelper->getTypes($logoBlockType->getName())
        );
        $this->assertSame(
            [$baseBlockType->getName(), $logoBlockType->getName()],
            $this->typeHelper->getTypeNames($logoBlockType->getName())
        );
        $this->assertTrue($this->typeHelper->isInstanceOf($logoBlockType->getName(), $logoBlockType->getName()));
        $this->assertTrue($this->typeHelper->isInstanceOf($logoBlockType->getName(), BaseType::NAME));
    }

    public function testWithAlreadyInitializedDerivedType()
    {
        $baseBlockType      = new BaseType();
        $containerBlockType = new ContainerType();
        $headerBlockType    = new HeaderType();

        $this->registry->expects($this->at(0))
            ->method('getType')
            ->with($headerBlockType->getName())
            ->will($this->returnValue($headerBlockType));
        $this->registry->expects($this->at(1))
            ->method('getType')
            ->with(ContainerType::NAME)
            ->will($this->returnValue($containerBlockType));
        $this->registry->expects($this->at(2))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue($baseBlockType));
        $this->registry->expects($this->exactly(3))
            ->method('getType');

        // get derived (here all types are added to the local cache of the BlockTypeHelper)
        $this->assertSame(
            [$baseBlockType, $containerBlockType, $headerBlockType],
            $this->typeHelper->getTypes($headerBlockType->getName())
        );
        $this->assertSame(
            [$baseBlockType->getName(), $containerBlockType->getName(), $headerBlockType->getName()],
            $this->typeHelper->getTypeNames($headerBlockType->getName())
        );
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), $headerBlockType->getName()));
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), ContainerType::NAME));
        $this->assertTrue($this->typeHelper->isInstanceOf($headerBlockType->getName(), BaseType::NAME));

        // get parent (here we test that the local cache of the BlockTypeHelper is used)
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
    }
}
