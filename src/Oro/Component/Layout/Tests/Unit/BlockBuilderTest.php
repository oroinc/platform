<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockBuilder;
use Oro\Component\Layout\BlockTypeHelperInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\RawLayout;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlockBuilderTest extends TestCase
{
    private RawLayout $rawLayout;
    private BlockTypeHelperInterface&MockObject $typeHelper;
    private LayoutContext $context;
    private LayoutManipulatorInterface&MockObject $layoutManipulator;
    private BlockBuilder $blockBuilder;

    #[\Override]
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

    public function testGetTypeHelper(): void
    {
        $this->assertSame($this->typeHelper, $this->blockBuilder->getTypeHelper());
    }

    public function testGetContext(): void
    {
        $this->assertSame($this->context, $this->blockBuilder->getContext());
    }

    public function testGetLayoutManipulator(): void
    {
        $this->assertSame($this->layoutManipulator, $this->blockBuilder->getLayoutManipulator());
    }

    public function testInitialize(): void
    {
        $id = 'test_id';

        $this->blockBuilder->initialize($id);

        $this->assertEquals($id, $this->blockBuilder->getId());
    }

    public function testGetTypeName(): void
    {
        $id = 'test_id';
        $name = 'test_name';

        $this->rawLayout->add($id, null, $name);

        $this->blockBuilder->initialize($id);

        $this->assertEquals($name, $this->blockBuilder->getTypeName());
    }

    public function testGetTypeNameWhenBlockTypeIsAddedAsObject(): void
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
