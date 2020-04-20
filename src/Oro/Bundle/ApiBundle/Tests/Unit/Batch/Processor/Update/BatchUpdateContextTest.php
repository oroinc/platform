<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class BatchUpdateContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var BatchUpdateContext */
    private $context;

    protected function setUp()
    {
        $this->context = new BatchUpdateContext();
    }

    public function testShouldSummaryBeInitialized()
    {
        self::assertInstanceOf(BatchSummary::class, $this->context->getSummary());
    }

    public function testGetOperationIdWhenItIsNotSet()
    {
        try {
            $this->context->getOperationId();
            self::fail('Expected TypeError');
        } catch (\TypeError $e) {
        }
    }

    public function testOperationId()
    {
        $operationId = 123;
        $this->context->setOperationId($operationId);
        self::assertSame($operationId, $this->context->getOperationId());
    }

    public function testHasUnexpectedErrors()
    {
        self::assertFalse($this->context->hasUnexpectedErrors());

        $this->context->setHasUnexpectedErrors(true);
        self::assertTrue($this->context->hasUnexpectedErrors());

        $this->context->setHasUnexpectedErrors(false);
        self::assertFalse($this->context->hasUnexpectedErrors());
    }

    public function testRetryAgain()
    {
        self::assertFalse($this->context->isRetryAgain());
        self::assertNull($this->context->getRetryReason());

        $reason = 'test retry reason';
        $this->context->setRetryReason($reason);
        self::assertTrue($this->context->isRetryAgain());
        self::assertEquals($reason, $this->context->getRetryReason());

        $this->context->setRetryReason(null);
        self::assertFalse($this->context->isRetryAgain());
        self::assertNull($this->context->getRetryReason());
    }

    public function testGetFileManagerWhenItIsNotSet()
    {
        try {
            $this->context->getFileManager();
            self::fail('Expected TypeError');
        } catch (\TypeError $e) {
        }
    }

    public function testFileManager()
    {
        $fileManager = $this->createMock(FileManager::class);
        $this->context->setFileManager($fileManager);
        self::assertSame($fileManager, $this->context->getFileManager());
    }

    public function testGetFileWhenItIsNotSet()
    {
        try {
            $this->context->getFile();
            self::fail('Expected TypeError');
        } catch (\TypeError $e) {
        }
    }

    public function testFile()
    {
        $file = new ChunkFile('api_1_chunk', 0, 0);
        $this->context->setFile($file);
        self::assertSame($file, $this->context->getFile());
    }

    public function testSupportedEntityClasses()
    {
        self::assertSame([], $this->context->getSupportedEntityClasses());

        $this->context->setSupportedEntityClasses(['Test\Class']);
        self::assertSame(['Test\Class'], $this->context->getSupportedEntityClasses());
    }

    public function testIncludedData()
    {
        self::assertNull($this->context->getIncludedData());

        $includedData = $this->createMock(IncludedData::class);
        $this->context->setIncludedData($includedData);
        self::assertSame($includedData, $this->context->getIncludedData());

        $this->context->setIncludedData(null);
        self::assertNull($this->context->getIncludedData());
    }

    public function testBatchItems()
    {
        self::assertNull($this->context->getBatchItems());

        $batchItem = $this->createMock(BatchUpdateItem::class);
        $this->context->setBatchItems([$batchItem]);
        $batchItems = $this->context->getBatchItems();
        self::assertCount(1, $batchItems);
        self::assertSame($batchItem, $batchItems[0]);

        $this->context->clearBatchItems();
        self::assertNull($this->context->getBatchItems());
    }

    public function testProcessedItemStatuses()
    {
        $item1 = $this->createMock(BatchUpdateItem::class);
        $item1->expects(self::any())
            ->method('getIndex')
            ->willReturn(0);
        $item2 = $this->createMock(BatchUpdateItem::class);
        $item2->expects(self::any())
            ->method('getIndex')
            ->willReturn(1);
        $item3 = $this->createMock(BatchUpdateItem::class);
        $item3->expects(self::any())
            ->method('getIndex')
            ->willReturn(2);

        self::assertNull($this->context->getProcessedItemStatuses());
        self::assertNull($this->context->getProcessedItemStatus($item1));

        $processedItemStatuses = [BatchUpdateItemStatus::HAS_ERRORS, BatchUpdateItemStatus::NO_ERRORS];
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        self::assertSame($processedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertSame(BatchUpdateItemStatus::HAS_ERRORS, $this->context->getProcessedItemStatus($item1));
        self::assertSame(BatchUpdateItemStatus::NO_ERRORS, $this->context->getProcessedItemStatus($item2));
        self::assertNull($this->context->getProcessedItemStatus($item3));

        $this->context->setProcessedItemStatuses(null);
        self::assertNull($this->context->getProcessedItemStatuses());
        self::assertNull($this->context->getProcessedItemStatus($item1));
    }

    public function testSharedData()
    {
        self::assertInstanceOf(ParameterBagInterface::class, $this->context->getSharedData());

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $this->context->setSharedData($sharedData);
        self::assertSame($sharedData, $this->context->getSharedData());
    }
}
