<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleArgumentsInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\EmailBundle\Command\Cron\EmailBodySyncCommand;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailBodySyncCommandTest extends TestCase
{
    private EmailBodySynchronizer&MockObject $synchronizer;
    private EmailBodySyncCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->synchronizer = $this->createMock(EmailBodySynchronizer::class);

        $this->command = new EmailBodySyncCommand($this->synchronizer);
    }

    public function testIsCronCommandScheduleDefinitionAware(): void
    {
        self::assertInstanceOf(CronCommandScheduleDefinitionInterface::class, $this->command);
    }

    public function testIsCronCommandScheduleArgumentsAware(): void
    {
        self::assertInstanceOf(CronCommandScheduleArgumentsInterface::class, $this->command);
    }

    public function testGetDefaultDefinition(): void
    {
        self::assertSame('*/30 * * * *', $this->command->getDefaultDefinition());
    }

    public function testGetDefaultArguments(): void
    {
        self::assertSame(['--process-timeout=1200'], $this->command->getDefaultArguments());
    }

    public function testGetDefaultArgumentsIsDerivedFromTimeoutConstants(): void
    {
        $expectedTimeout = EmailBodySyncCommand::MAX_EXEC_TIME_IN_MIN * 60
            + EmailBodySyncCommand::PROCESS_TIMEOUT_MARGIN_IN_SEC;

        self::assertSame(
            [sprintf('--process-timeout=%d', $expectedTimeout)],
            $this->command->getDefaultArguments()
        );
    }

    public function testGetDefaultArgumentsProcessTimeoutExceedsMaxExecTime(): void
    {
        [$argument] = $this->command->getDefaultArguments();
        [, $timeout] = explode('=', $argument);

        self::assertGreaterThan(EmailBodySyncCommand::MAX_EXEC_TIME_IN_MIN * 60, (int)$timeout);
    }
}
