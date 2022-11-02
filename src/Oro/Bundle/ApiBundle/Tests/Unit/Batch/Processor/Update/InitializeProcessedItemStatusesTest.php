<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\InitializeProcessedItemStatuses;

class InitializeProcessedItemStatusesTest extends BatchUpdateProcessorTestCase
{
    /** @var InitializeProcessedItemStatuses */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new InitializeProcessedItemStatuses();
    }

    public function testProcessWhenStatusesAreAlreadyInitialized()
    {
        $processedItemStatuses = [BatchUpdateItemStatus::HAS_ERRORS];
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setResult([['key' => 'val1']]);
        $this->processor->process($this->context);
        self::assertSame($processedItemStatuses, $this->context->getProcessedItemStatuses());
    }

    public function testProcessWhenNoDataRecords()
    {
        $this->processor->process($this->context);
        self::assertNull($this->context->getProcessedItemStatuses());
    }

    public function testProcess()
    {
        $this->context->setResult([['key' => 'val1'], ['key' => 'val2']]);
        $this->processor->process($this->context);
        self::assertSame(
            [BatchUpdateItemStatus::NOT_PROCESSED, BatchUpdateItemStatus::NOT_PROCESSED],
            $this->context->getProcessedItemStatuses()
        );
    }
}
