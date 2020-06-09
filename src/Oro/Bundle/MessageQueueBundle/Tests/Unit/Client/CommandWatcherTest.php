<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\CommandWatcher;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

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

    public function testShouldDoNothingOnCommandStartIfCommandIsNull()
    {
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects(self::once())
            ->method('getCommand')
            ->willReturn(null);
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldDoNothingOnCommandStartIfCommandIsConsumeMessagesCommand()
    {
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects(self::once())
            ->method('getCommand')
            ->willReturn($this->createMock(ConsumeMessagesCommand::class));
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldEnableBufferingOnCommandStartIfBufferingIsNotEnabledYet()
    {
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects(self::once())
            ->method('getCommand')
            ->willReturn($this->createMock(Command::class));
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldNotEnableBufferingOnCommandStartIfBufferingIsAlreadyEnabled()
    {
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects(self::once())
            ->method('getCommand')
            ->willReturn($this->createMock(Command::class));
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->commandWatcher->onCommandStart($event);
    }

    public function testShouldFlushBufferOnCommandEndIfBufferingIsEnabledAndHasMessagesInBuffer()
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

    public function testShouldNotFlushBufferOnCommandEndIfBufferingIsEnabledButNoMessagesInBuffer()
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

    public function testShouldNotFlushBufferOnCommandEndIfBufferingIsNotEnabled()
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
