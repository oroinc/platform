<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\CommandWatcher;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandWatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private $bufferedProducer;

    /** @var CommandWatcher */
    private $commandWatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->commandWatcher = new CommandWatcher($this->bufferedProducer);
    }

    public function testShouldDoNothingOnCommandStartIfCommandIsNull(): void
    {
        $event = new ConsoleCommandEvent(
            null,
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldDoNothingOnCommandStartIfCommandIsConsumeMessagesCommand(): void
    {
        $event = new ConsoleCommandEvent(
            $this->createMock(ConsumeMessagesCommand::class),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldEnableBufferingOnCommandStartIfBufferingIsNotEnabledYet(): void
    {
        $event = new ConsoleCommandEvent(
            $this->createMock(Command::class),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldNotEnableBufferingOnCommandStartIfBufferingIsAlreadyEnabled(): void
    {
        $event = new ConsoleCommandEvent(
            $this->createMock(Command::class),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldFlushBufferOnCommandEndIfBufferingIsEnabledAndHasMessagesInBuffer(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $this->commandWatcher->onCommandEnd();
    }

    public function testShouldNotFlushBufferOnCommandEndIfBufferingIsEnabledButNoMessagesInBuffer(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->commandWatcher->onCommandEnd();
    }

    public function testShouldNotFlushBufferOnCommandEndIfBufferingIsNotEnabled(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('hasBufferedMessages');
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->commandWatcher->onCommandEnd();
    }
}
