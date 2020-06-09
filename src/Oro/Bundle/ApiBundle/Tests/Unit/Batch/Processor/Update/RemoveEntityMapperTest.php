<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\RemoveEntityMapper;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;

class RemoveEntityMapperTest extends BatchUpdateProcessorTestCase
{
    /** @var RemoveEntityMapper */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveEntityMapper();
    }

    public function testProcessWhenNoBatchItems()
    {
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $item1 = $this->createMock(BatchUpdateItem::class);
        $item1Context = $this->createMock(BatchUpdateItemContext::class);
        $item1TargetContext = $this->createMock(CreateContext::class);
        $item1->expects(self::once())
            ->method('getContext')
            ->willReturn($item1Context);
        $item1Context->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($item1TargetContext);
        $item1TargetContext->expects(self::once())
            ->method('setEntityMapper')
            ->with(self::isNull());

        $item2 = $this->createMock(BatchUpdateItem::class);
        $item2Context = $this->createMock(BatchUpdateItemContext::class);
        $item2->expects(self::once())
            ->method('getContext')
            ->willReturn($item2Context);
        $item2Context->expects(self::once())
            ->method('getTargetContext')
            ->willReturn(null);

        $this->context->setBatchItems([$item1, $item2]);
        $this->processor->process($this->context);
    }
}
