<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleArgumentsInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface;
use Oro\Bundle\ImapBundle\Command\Cron\EmailSyncCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailSyncCommandTest extends TestCase
{
    private EmailSynchronizerInterface&MockObject $imapEmailSynchronizer;
    private EmailSyncCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->imapEmailSynchronizer = $this->createMock(EmailSynchronizerInterface::class);

        $this->command = new EmailSyncCommand($this->imapEmailSynchronizer);
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
        self::assertSame('*/1 * * * *', $this->command->getDefaultDefinition());
    }

    public function testGetDefaultArguments(): void
    {
        self::assertSame(['--process-timeout=1200'], $this->command->getDefaultArguments());
    }

    public function testGetDefaultArgumentsIsDerivedFromTimeoutConstants(): void
    {
        $expectedTimeout = EmailSyncCommand::MAX_EXEC_TIME_IN_MIN * 60
            + EmailSyncCommand::PROCESS_TIMEOUT_MARGIN_IN_SEC;

        self::assertSame(
            [sprintf('--process-timeout=%d', $expectedTimeout)],
            $this->command->getDefaultArguments()
        );
    }

    public function testGetDefaultArgumentsProcessTimeoutExceedsMaxExecTime(): void
    {
        [$argument] = $this->command->getDefaultArguments();
        [, $timeout] = explode('=', $argument);

        self::assertGreaterThan(EmailSyncCommand::MAX_EXEC_TIME_IN_MIN * 60, (int)$timeout);
    }
}
