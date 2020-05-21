<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\UpdateSummaryCounters;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;

class UpdateSummaryCountersTest extends BatchUpdateProcessorTestCase
{
    /** @var UpdateSummaryCounters */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new UpdateSummaryCounters();
    }

    public function testProcessWithoutBatchItems()
    {
        $this->context->setProcessedItemStatuses([]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }

    public function testProcessWithCreateBatchItem()
    {
        $itemContext = new BatchUpdateItemContext();
        $itemContext->setTargetAction(ApiAction::CREATE);
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(1, $this->context->getSummary()->getWriteCount());
        self::assertSame(1, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }

    public function testProcessWithUpdateBatchItem()
    {
        $itemContext = new BatchUpdateItemContext();
        $itemContext->setTargetAction(ApiAction::UPDATE);
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(1, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(1, $this->context->getSummary()->getUpdateCount());
    }

    public function testProcessWithUnknownBatchItem()
    {
        $itemContext = new BatchUpdateItemContext();
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }

    public function testProcessWhenErrorOccurredWhenProcessingBatchItem()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getContext');

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::HAS_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }
}
