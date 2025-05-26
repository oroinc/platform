<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateResponse;
use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntities;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use PHPUnit\Framework\TestCase;

class BatchUpdateResponseTest extends TestCase
{
    public function testResponse(): void
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();
        $affectedEntities = new BatchAffectedEntities();

        $response = new BatchUpdateResponse($data, $processedItemStatuses, $summary, $affectedEntities, false);
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertSame($affectedEntities, $response->getAffectedEntities());
        self::assertFalse($response->hasUnexpectedErrors());
        self::assertFalse($response->isRetryAgain());
        self::assertNull($response->getRetryReason());
    }

    public function testResponseWithUnexpectedErrors(): void
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();
        $affectedEntities = new BatchAffectedEntities();

        $response = new BatchUpdateResponse($data, $processedItemStatuses, $summary, $affectedEntities, true);
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertSame($affectedEntities, $response->getAffectedEntities());
        self::assertTrue($response->hasUnexpectedErrors());
        self::assertFalse($response->isRetryAgain());
        self::assertNull($response->getRetryReason());
    }

    public function testResponseWithRetryAgain(): void
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();
        $affectedEntities = new BatchAffectedEntities();
        $retryReason = 'test retry reason';

        $response = new BatchUpdateResponse(
            $data,
            $processedItemStatuses,
            $summary,
            $affectedEntities,
            false,
            $retryReason
        );
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertSame($affectedEntities, $response->getAffectedEntities());
        self::assertFalse($response->hasUnexpectedErrors());
        self::assertTrue($response->isRetryAgain());
        self::assertEquals($retryReason, $response->getRetryReason());
    }
}
