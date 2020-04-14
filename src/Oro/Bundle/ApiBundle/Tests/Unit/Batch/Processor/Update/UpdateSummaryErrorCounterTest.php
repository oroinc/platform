<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\UpdateSummaryErrorCounter;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Model\Error;

class UpdateSummaryErrorCounterTest extends BatchUpdateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RetryHelper */
    private $retryHelper;

    /** @var UpdateSummaryErrorCounter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retryHelper = $this->createMock(RetryHelper::class);
        $this->processor = new UpdateSummaryErrorCounter($this->retryHelper);
    }

    public function testProcessNoErrors()
    {
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getErrorCount());
    }

    public function testProcessWhenItemHasErrorsToSave()
    {
        $rawItems = [['key' => '1']];
        $processedItemStatuses = [0];
        $hasItemsToRetry = true;

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([new Error(), new Error()]);

        $this->retryHelper->expects(self::once())
            ->method('hasItemsToRetry')
            ->with($rawItems, $processedItemStatuses)
            ->willReturn($hasItemsToRetry);
        $this->retryHelper->expects(self::once())
            ->method('hasItemErrorsToSave')
            ->with($item, $hasItemsToRetry, $processedItemStatuses)
            ->willReturn(true);

        $this->context->addError(new Error());
        $this->context->setBatchItems([$item]);
        $this->context->setResult($rawItems);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);

        self::assertSame(3, $this->context->getSummary()->getErrorCount());
    }

    public function testProcessWhenItemDoesNotHaveErrorsToSave()
    {
        $rawItems = [['key' => '1']];
        $processedItemStatuses = [0];
        $hasItemsToRetry = true;

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::never())
            ->method('getContext')
            ->willReturn($itemContext);

        $this->retryHelper->expects(self::once())
            ->method('hasItemsToRetry')
            ->with($rawItems, $processedItemStatuses)
            ->willReturn($hasItemsToRetry);
        $this->retryHelper->expects(self::once())
            ->method('hasItemErrorsToSave')
            ->with($item, $hasItemsToRetry, $processedItemStatuses)
            ->willReturn(false);

        $this->context->addError(new Error());
        $this->context->setBatchItems([$item]);
        $this->context->setResult($rawItems);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);

        self::assertSame(1, $this->context->getSummary()->getErrorCount());
    }
}
