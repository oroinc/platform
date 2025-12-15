<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\PostUpgrade;

use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskInterface;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskRegistry;
use PHPUnit\Framework\TestCase;

final class PostUpgradeTaskRegistryTest extends TestCase
{
    public function testConstructorRegistersTasksByName(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::once())
            ->method('getName')
            ->willReturn('task_1');

        $task2 = $this->createMock(PostUpgradeTaskInterface::class);
        $task2->expects(self::once())
            ->method('getName')
            ->willReturn('task_2');

        $registry = new PostUpgradeTaskRegistry([$task1, $task2]);

        self::assertSame($task1, $registry->getTaskByName('task_1'));
        self::assertSame($task2, $registry->getTaskByName('task_2'));
    }

    public function testGetAllTasks(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::once())
            ->method('getName')
            ->willReturn('task_1');

        $task2 = $this->createMock(PostUpgradeTaskInterface::class);
        $task2->expects(self::once())
            ->method('getName')
            ->willReturn('task_2');

        $task3 = $this->createMock(PostUpgradeTaskInterface::class);
        $task3->expects(self::once())
            ->method('getName')
            ->willReturn('task_3');

        $registry = new PostUpgradeTaskRegistry([$task1, $task2, $task3]);

        $allTasks = $registry->getAllTasks();

        self::assertCount(3, $allTasks);
        self::assertContains($task1, $allTasks);
        self::assertContains($task2, $allTasks);
        self::assertContains($task3, $allTasks);
    }

    public function testGetTaskByNameReturnsNullForNonExistentTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::once())
            ->method('getName')
            ->willReturn('existing_task');

        $registry = new PostUpgradeTaskRegistry([$task]);

        self::assertNull($registry->getTaskByName('non_existent_task'));
    }

    public function testHasTaskReturnsTrueForExistingTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::once())
            ->method('getName')
            ->willReturn('existing_task');

        $registry = new PostUpgradeTaskRegistry([$task]);

        self::assertTrue($registry->hasTask('existing_task'));
    }

    public function testHasTaskReturnsFalseForNonExistentTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::once())
            ->method('getName')
            ->willReturn('existing_task');

        $registry = new PostUpgradeTaskRegistry([$task]);

        self::assertFalse($registry->hasTask('non_existent_task'));
    }

    public function testWithEmptyTaskList(): void
    {
        $registry = new PostUpgradeTaskRegistry([]);

        self::assertEmpty($registry->getAllTasks());
        self::assertNull($registry->getTaskByName('any_task'));
        self::assertFalse($registry->hasTask('any_task'));
    }
}
