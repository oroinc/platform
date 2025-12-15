<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Command;

use Oro\Bundle\PlatformBundle\Command\PostUpgradeTasksCommand;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskInterface;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskRegistry;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PostUpgradeTasksCommandTest extends TestCase
{
    private PostUpgradeTaskRegistry&MockObject $taskRegistry;
    private PostUpgradeTasksCommand $command;
    private CommandTester $commandTester;

    #[\Override]
    protected function setUp(): void
    {
        $this->taskRegistry = $this->createMock(PostUpgradeTaskRegistry::class);
        $this->command = new PostUpgradeTasksCommand($this->taskRegistry);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandNameAndDescription(): void
    {
        self::assertSame('oro:platform:post-upgrade-tasks', $this->command->getName());
        self::assertSame(
            'Schedules background jobs to fix data asynchronously after upgrade.',
            $this->command->getDescription()
        );
    }

    public function testExecuteWithoutOptionsReturnsInvalid(): void
    {
        $this->taskRegistry->expects(self::never())
            ->method('getAllTasks');

        $statusCode = $this->commandTester->execute([]);

        self::assertSame(Command::INVALID, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Please specify --task=NAME, --all, or --list option', $output);
        self::assertStringContainsString('Use --list to see all available tasks', $output);
    }

    public function testExecuteWithListOptionShowsEmptyTaskList(): void
    {
        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([]);

        $statusCode = $this->commandTester->execute(['--list' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('No post-upgrade tasks are registered', $output);
    }

    public function testExecuteWithListOptionShowsAvailableTasks(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::once())
            ->method('getName')
            ->willReturn('task_one');
        $task1->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description for task one');

        $task2 = $this->createMock(PostUpgradeTaskInterface::class);
        $task2->expects(self::once())
            ->method('getName')
            ->willReturn('task_two');
        $task2->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description for task two');

        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([$task1, $task2]);

        $statusCode = $this->commandTester->execute(['--list' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Available Post-Upgrade Tasks', $output);
        self::assertStringContainsString('task_one', $output);
        self::assertStringContainsString('Description for task one', $output);
        self::assertStringContainsString('task_two', $output);
        self::assertStringContainsString('Description for task two', $output);
        self::assertStringContainsString('Execute a specific task:', $output);
        self::assertStringContainsString('Execute all tasks:', $output);
    }

    public function testExecuteWithInvalidBatchSize(): void
    {
        $statusCode = $this->commandTester->execute(['--all' => true, '--batch-size' => '0']);

        self::assertSame(Command::INVALID, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The batch size must be a positive integer', $output);
    }

    public function testExecuteWithAllOptionAndNoTasks(): void
    {
        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([]);

        $statusCode = $this->commandTester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('No post-upgrade tasks are registered', $output);
    }

    public function testExecuteWithAllOptionExecutesAllTasks(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::any())
            ->method('getName')
            ->willReturn('task_one');
        $task1->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('task_one', true, 10, 'Task one completed'));

        $task2 = $this->createMock(PostUpgradeTaskInterface::class);
        $task2->expects(self::any())
            ->method('getName')
            ->willReturn('task_two');
        $task2->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('task_two', true, 5, 'Task two completed'));

        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([$task1, $task2]);

        $statusCode = $this->commandTester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Executing All Post-Upgrade Tasks', $output);
        self::assertStringContainsString('[task_one] Task one completed', $output);
        self::assertStringContainsString('[task_two] Task two completed', $output);
        self::assertStringContainsString('Executed 2 of 2 tasks, scheduled 15 items total', $output);
    }

    public function testExecuteWithAllOptionHandlesSkippedTasks(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::any())
            ->method('getName')
            ->willReturn('task_one');
        $task1->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('task_one', true, 10, 'Task one completed'));

        $task2 = $this->createMock(PostUpgradeTaskInterface::class);
        $task2->expects(self::any())
            ->method('getName')
            ->willReturn('task_two');
        $task2->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('task_two', false, null, 'No work needed'));

        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([$task1, $task2]);

        $statusCode = $this->commandTester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('[task_one] Task one completed', $output);
        self::assertStringContainsString('[task_two] No work needed', $output);
        self::assertStringContainsString('Executed 1 of 2 tasks, scheduled 10 items total', $output);
    }

    public function testExecuteWithAllOptionWithoutScheduledCount(): void
    {
        $task1 = $this->createMock(PostUpgradeTaskInterface::class);
        $task1->expects(self::any())
            ->method('getName')
            ->willReturn('task_one');
        $task1->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('task_one', true, null, 'Task one completed'));

        $this->taskRegistry->expects(self::once())
            ->method('getAllTasks')
            ->willReturn([$task1]);

        $statusCode = $this->commandTester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Executed 1 of 1 tasks', $output);
        self::assertStringNotContainsString('scheduled', $output);
    }

    public function testExecuteWithTaskOptionAndTaskNotFound(): void
    {
        $this->taskRegistry->expects(self::once())
            ->method('getTaskByName')
            ->with('non_existent_task')
            ->willReturn(null);

        $statusCode = $this->commandTester->execute(['--task' => 'non_existent_task']);

        self::assertSame(Command::FAILURE, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Task "non_existent_task" not found', $output);
        self::assertStringContainsString('Use --list to see all available tasks', $output);
    }

    public function testExecuteWithTaskOptionExecutesSpecificTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::any())
            ->method('getName')
            ->willReturn('my_task');
        $task->expects(self::once())
            ->method('getDescription')
            ->willReturn('My task description');
        $task->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('my_task', true, 20, 'Task completed successfully'));

        $this->taskRegistry->expects(self::once())
            ->method('getTaskByName')
            ->with('my_task')
            ->willReturn($task);

        $statusCode = $this->commandTester->execute(['--task' => 'my_task']);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Executing Task: my_task', $output);
        self::assertStringContainsString('My task description', $output);
        self::assertStringContainsString('Task completed successfully', $output);
    }

    public function testExecuteWithTaskOptionHandlesSkippedTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::any())
            ->method('getName')
            ->willReturn('my_task');
        $task->expects(self::once())
            ->method('getDescription')
            ->willReturn('My task description');
        $task->expects(self::once())
            ->method('execute')
            ->willReturn(new PostUpgradeTaskResult('my_task', false, null, 'Nothing to do'));

        $this->taskRegistry->expects(self::once())
            ->method('getTaskByName')
            ->with('my_task')
            ->willReturn($task);

        $statusCode = $this->commandTester->execute(['--task' => 'my_task']);

        self::assertSame(Command::SUCCESS, $statusCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Nothing to do', $output);
    }

    public function testExecutePassesBatchSizeToTask(): void
    {
        $task = $this->createMock(PostUpgradeTaskInterface::class);
        $task->expects(self::any())
            ->method('getName')
            ->willReturn('my_task');
        $task->expects(self::once())
            ->method('getDescription')
            ->willReturn('My task description');
        $task->expects(self::once())
            ->method('execute')
            ->with(
                self::callback(function ($input) {
                    return $input->getOption('batch-size') === '1000';
                }),
                self::anything(),
                self::anything()
            )
            ->willReturn(new PostUpgradeTaskResult('my_task', true));

        $this->taskRegistry->expects(self::once())
            ->method('getTaskByName')
            ->with('my_task')
            ->willReturn($task);

        $this->commandTester->execute(['--task' => 'my_task', '--batch-size' => '1000']);
    }
}
