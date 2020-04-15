<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateResponse;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;

class BatchUpdateResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testResponse()
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();

        $response = new BatchUpdateResponse($data, $processedItemStatuses, $summary, false);
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertFalse($response->hasUnexpectedErrors());
        self::assertFalse($response->isRetryAgain());
        self::assertNull($response->getRetryReason());
    }

    public function testResponseWithUnexpectedErrors()
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();

        $response = new BatchUpdateResponse($data, $processedItemStatuses, $summary, true);
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertTrue($response->hasUnexpectedErrors());
        self::assertFalse($response->isRetryAgain());
        self::assertNull($response->getRetryReason());
    }

    public function testResponseWithRetryAgain()
    {
        $data = [['key' => 'val']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];
        $summary = new BatchSummary();
        $retryReason = 'test retry reason';

        $response = new BatchUpdateResponse(
            $data,
            $processedItemStatuses,
            $summary,
            false,
            $retryReason
        );
        self::assertSame($data, $response->getData());
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses());
        self::assertSame($summary, $response->getSummary());
        self::assertFalse($response->hasUnexpectedErrors());
        self::assertTrue($response->isRetryAgain());
        self::assertEquals($retryReason, $response->getRetryReason());
    }
}
