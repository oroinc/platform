<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use PHPUnit\Framework\TestCase;

class BatchSummaryTest extends TestCase
{
    public function testInitialReadCount(): void
    {
        $summary = new BatchSummary();

        self::assertSame(0, $summary->getReadCount());
    }

    public function testInitialWriteCount(): void
    {
        $summary = new BatchSummary();

        self::assertSame(0, $summary->getWriteCount());
    }

    public function testInitialErrorCount(): void
    {
        $summary = new BatchSummary();

        self::assertSame(0, $summary->getErrorCount());
    }

    public function testInitialCreateCount(): void
    {
        $summary = new BatchSummary();

        self::assertSame(0, $summary->getCreateCount());
    }

    public function testInitialUpdateCount(): void
    {
        $summary = new BatchSummary();

        self::assertSame(0, $summary->getUpdateCount());
    }

    public function testIncrementReadCount(): void
    {
        $summary = new BatchSummary();

        $summary->incrementReadCount();
        self::assertSame(1, $summary->getReadCount());
        $summary->incrementReadCount(2);
        self::assertSame(3, $summary->getReadCount());
    }

    public function testIncrementWriteCount(): void
    {
        $summary = new BatchSummary();

        $summary->incrementWriteCount();
        self::assertSame(1, $summary->getWriteCount());
        $summary->incrementWriteCount(2);
        self::assertSame(3, $summary->getWriteCount());
    }

    public function testIncrementErrorCount(): void
    {
        $summary = new BatchSummary();

        $summary->incrementErrorCount();
        self::assertSame(1, $summary->getErrorCount());
        $summary->incrementErrorCount(2);
        self::assertSame(3, $summary->getErrorCount());
    }

    public function testIncrementCreateCount(): void
    {
        $summary = new BatchSummary();

        $summary->incrementCreateCount();
        self::assertSame(1, $summary->getCreateCount());
        $summary->incrementCreateCount(2);
        self::assertSame(3, $summary->getCreateCount());
    }

    public function testIncrementUpdateCount(): void
    {
        $summary = new BatchSummary();

        $summary->incrementUpdateCount();
        self::assertSame(1, $summary->getUpdateCount());
        $summary->incrementUpdateCount(2);
        self::assertSame(3, $summary->getUpdateCount());
    }
}
