<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\PostUpgrade;

use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskResult;
use PHPUnit\Framework\TestCase;

final class PostUpgradeTaskResultTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $taskName = 'test_task';
        $executed = true;
        $scheduledCount = 100;
        $message = 'Task completed successfully';

        $result = new PostUpgradeTaskResult($taskName, $executed, $scheduledCount, $message);

        self::assertSame($taskName, $result->getTaskName());
        self::assertTrue($result->isExecuted());
        self::assertSame($scheduledCount, $result->getScheduledCount());
        self::assertSame($message, $result->getMessage());
    }

    public function testWithMinimalParameters(): void
    {
        $result = new PostUpgradeTaskResult('task_name', true);

        self::assertSame('task_name', $result->getTaskName());
        self::assertTrue($result->isExecuted());
        self::assertNull($result->getScheduledCount());
        self::assertNull($result->getMessage());
    }

    public function testWithNotExecuted(): void
    {
        $result = new PostUpgradeTaskResult('task_name', false);

        self::assertSame('task_name', $result->getTaskName());
        self::assertFalse($result->isExecuted());
        self::assertNull($result->getScheduledCount());
        self::assertNull($result->getMessage());
    }

    public function testWithScheduledCount(): void
    {
        $result = new PostUpgradeTaskResult('task_name', true, 500);

        self::assertSame('task_name', $result->getTaskName());
        self::assertTrue($result->isExecuted());
        self::assertSame(500, $result->getScheduledCount());
        self::assertNull($result->getMessage());
    }

    public function testWithZeroScheduledCount(): void
    {
        $result = new PostUpgradeTaskResult('task_name', true, 0);

        self::assertSame(0, $result->getScheduledCount());
        self::assertTrue($result->isExecuted());
    }

    public function testExecutedWithScheduledCountAndMessage(): void
    {
        $result = new PostUpgradeTaskResult('async_task', true, 1000, 'Scheduled 1000 items');

        self::assertTrue($result->isExecuted());
        self::assertSame(1000, $result->getScheduledCount());
        self::assertSame('Scheduled 1000 items', $result->getMessage());
    }

    public function testNotExecutedWithMessage(): void
    {
        $result = new PostUpgradeTaskResult('skipped_task', false, null, 'No work needed');

        self::assertFalse($result->isExecuted());
        self::assertNull($result->getScheduledCount());
        self::assertSame('No work needed', $result->getMessage());
    }
}
