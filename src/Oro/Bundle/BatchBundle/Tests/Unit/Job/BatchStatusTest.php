<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Job;

use Oro\Bundle\BatchBundle\Job\BatchStatus;

class BatchStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testToString(): void
    {
        self::assertEquals('ABANDONED', new BatchStatus(BatchStatus::ABANDONED));
    }

    public function testSetValue(): void
    {
        $batchStatus = new BatchStatus(BatchStatus::UNKNOWN);
        $batchStatus->setValue(BatchStatus::FAILED);

        self::assertEquals(BatchStatus::FAILED, $batchStatus->getValue());
    }

    public function testMaxStatus(): void
    {
        self::assertEquals(
            BatchStatus::FAILED,
            BatchStatus::max(BatchStatus::FAILED, BatchStatus::COMPLETED)
        );

        self::assertEquals(
            BatchStatus::FAILED,
            BatchStatus::max(BatchStatus::COMPLETED, BatchStatus::FAILED)
        );

        self::assertEquals(
            BatchStatus::FAILED,
            BatchStatus::max(BatchStatus::FAILED, BatchStatus::FAILED)
        );

        self::assertEquals(
            BatchStatus::STARTED,
            BatchStatus::max(BatchStatus::STARTED, BatchStatus::STARTING)
        );

        self::assertEquals(
            BatchStatus::STARTED,
            BatchStatus::max(BatchStatus::COMPLETED, BatchStatus::STARTED)
        );
    }

    public function testUpgradeStatusFinished(): void
    {
        $failed = new BatchStatus(BatchStatus::FAILED);

        self::assertEquals(
            new BatchStatus(BatchStatus::FAILED),
            $failed->upgradeTo(BatchStatus::COMPLETED)
        );

        $completed = new BatchStatus(BatchStatus::COMPLETED);
        self::assertEquals(
            new BatchStatus(BatchStatus::FAILED),
            $completed->upgradeTo(BatchStatus::FAILED)
        );
    }

    public function testUpgradeStatusUnfinished(): void
    {
        $starting = new BatchStatus(BatchStatus::STARTING);
        self::assertEquals(
            new BatchStatus(BatchStatus::COMPLETED),
            $starting->upgradeTo(BatchStatus::COMPLETED)
        );

        $completed = new BatchStatus(BatchStatus::COMPLETED);
        self::assertEquals(
            new BatchStatus(BatchStatus::COMPLETED),
            $completed->upgradeTo(BatchStatus::STARTING)
        );

        $starting = new BatchStatus(BatchStatus::STARTING);
        self::assertEquals(
            new BatchStatus(BatchStatus::STARTED),
            $starting->upgradeTo(BatchStatus::STARTED)
        );

        $started = new BatchStatus(BatchStatus::STARTED);
        self::assertEquals(
            new BatchStatus(BatchStatus::STARTED),
            $started->upgradeTo(BatchStatus::STARTING)
        );
    }

    public function testIsRunning(): void
    {
        $failed = new BatchStatus(BatchStatus::FAILED);
        self::assertFalse($failed->isRunning());

        $completed = new  BatchStatus(BatchStatus::COMPLETED);
        self::assertFalse($completed->isRunning());

        $started = new BatchStatus(BatchStatus::STARTED);
        self::assertTrue($started->isRunning());

        $starting = new BatchStatus(BatchStatus::STARTING);
        self::assertTrue($starting->isRunning());
    }

    public function testIsUnsuccessful(): void
    {
        $failed = new BatchStatus(BatchStatus::FAILED);
        self::assertTrue($failed->isUnsuccessful());

        $completed = new BatchStatus(BatchStatus::COMPLETED);
        self::assertFalse($completed->isUnsuccessful());

        $started = new BatchStatus(BatchStatus::STARTED);
        self::assertFalse($started->isUnsuccessful());

        $starting = new BatchStatus(BatchStatus::STARTING);
        self::assertFalse($starting->isUnsuccessful());
    }

    public function testGetAllLabels(): void
    {
        $expectedLabels = [
            BatchStatus::COMPLETED => 'COMPLETED',
            BatchStatus::STARTING => 'STARTING',
            BatchStatus::STARTED => 'STARTED',
            BatchStatus::STOPPING => 'STOPPING',
            BatchStatus::STOPPED => 'STOPPED',
            BatchStatus::FAILED => 'FAILED',
            BatchStatus::ABANDONED => 'ABANDONED',
            BatchStatus::UNKNOWN => 'UNKNOWN',
        ];

        self::assertEquals($expectedLabels, BatchStatus::getAllLabels());
    }
}
