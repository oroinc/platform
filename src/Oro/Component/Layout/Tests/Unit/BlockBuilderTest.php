<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockBuilder;
use Oro\Component\Layout\BlockTypeHelperInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\RawLayout;

class BlockBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RawLayout */
    private $rawLayout;

    /** @var BlockTypeHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $typeHelper;

    /** @var LayoutContext */
    private $context;

    /** @var LayoutManipulatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManipulator;

    /** @var BlockBuilder */
    private $blockBuilder;

    protected function setUp(): void
    {
        $this->rawLayout = new RawLayout();
        $this->typeHelper = $this->createMock(BlockTypeHelperInterface::class);
        $this->context = new LayoutContext();
        $this->layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);

        $this->blockBuilder = new BlockBuilder(
            $this->layoutManipulator,
            $this->rawLayout,
            $this->typeHelper,
            $this->context
        );
    }

    public function testGetTypeHelper()
    {
        $this->assertSame($this->typeHelper, $this->blockBuilder->getTypeHelper());
    }

    public function testGetContext()
    {
        $this->assertSame($this->context, $this->blockBuilder->getContext());
    }

    public function testGetLayoutManipulator()
    {
        $this->assertSame($this->layoutManipulator, $this->blockBuilder->getLayoutManipulator());
    }

    public function testInitialize()
    {
        $id = 'test_id';

        $this->blockBuilder->initialize($id);

        $this->assertEquals($id, $this->blockBuilder->getId());
    }

    public function testGetTypeName()
    {
        $id = 'test_id';
        $name = 'test_name';

        $this->rawLayout->add($id, null, $name);

        $this->blockBuilder->initialize($id);

        $this->assertEquals($name, $this->blockBuilder->getTypeName());
    }

    public function testGetTypeNameWhenBlockTypeIsAddedAsObject()
    {
        $id = 'test_id';
        $name = 'test_name';

        $type = $this->createMock(BlockTypeInterface::class);
        $type->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->rawLayout->add($id, null, $type);

        $this->blockBuilder->initialize($id);

        $this->assertEquals($name, $this->blockBuilder->getTypeName());
    }
}
