<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleHandler;
use Oro\Component\MessageQueue\Log\ConsumerState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHandlerTest extends TestCase
{
    private ConsumerState $consumerState;
    private OutputInterface&MockObject $output;
    private ConsoleHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();
        $this->output = $this->createMock(OutputInterface::class);

        $this->handler = new ConsoleHandler($this->consumerState);
    }

    public function testIsHandlingWhenConsumptionIsNotStarted(): void
    {
        $this->output->expects(self::never())
            ->method('getVerbosity');

        $input = $this->createMock(InputInterface::class);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        self::assertFalse($this->handler->isHandling($record));
    }

    public function testIsHandlingWhenConsumptionIsStarted(): void
    {
        $this->output->expects(self::once())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_DEBUG);

        $this->consumerState->startConsumption();
        $input = $this->createMock(InputInterface::class);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        self::assertTrue($this->handler->isHandling($record));
    }

    public function testHandleWhenConsumptionIsNotStarted(): void
    {
        $this->output->expects(self::never())
            ->method('getVerbosity');
        $this->output->expects(self::never())
            ->method('write');

        $input = $this->createMock(InputInterface::class);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        self::assertFalse($this->handler->handle($record));
    }

    public function testHandleWhenConsumptionIsStarted(): void
    {
        $this->output->expects(self::any())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_DEBUG);
        $this->output->expects(self::once())
            ->method('write')
            ->with(
                '2018-07-06 09:16:05 <fg=white>app.DEBUG</>: test message '
                . '["key" => "value"] ["processor" => "Test\Processor"]'
                . "\n"
            );

        $this->consumerState->startConsumption();
        $input = $this->createMock(InputInterface::class);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2018-07-06 09:16:05'),
            channel: 'app',
            level: Level::Debug,
            message: 'test message',
            context: ['key' => 'value'],
            extra: ['processor' => 'Test\Processor']
        );
        self::assertFalse($this->handler->handle($record));
    }

    public function testCommandEventSubscriber(): void
    {
        $this->consumerState->startConsumption();
        $input = $this->createMock(InputInterface::class);
        $this->handler->onCommand(new ConsoleCommandEvent(null, $input, $this->output));
        $handler = new ConsoleHandler($this->consumerState);

        $commandEvent = new ConsoleCommandEvent(
            $this->createMock(Command::class),
            $this->createMock(InputInterface::class),
            $this->output
        );
        $nestedCommandEvent = new ConsoleCommandEvent(
            $this->createMock(Command::class),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        // start a root command
        $handler->onCommand($commandEvent);
        // start a nested command
        $handler->onCommand($nestedCommandEvent);

        // test that the output of a root command is set
        $this->output->expects(self::any())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_DEBUG);
        $this->output->expects(self::once())
            ->method('write')
            ->with('2018-07-06 09:16:05 <fg=white>app.DEBUG</>: test message' . "\n");
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2018-07-06 09:16:05'),
            channel: 'app',
            level: Level::Debug,
            message: 'test message',
            context: [],
            extra: []
        );
        self::assertFalse($this->handler->handle($record));

        // test that the output is not removed when a nested command terminates
        $handler->onTerminate(
            new ConsoleTerminateEvent(
                $nestedCommandEvent->getCommand(),
                $nestedCommandEvent->getInput(),
                $nestedCommandEvent->getOutput(),
                0
            )
        );
        self::assertTrue($handler->isHandling($record));

        // test that the output is removed when a root command terminates
        $handler->onTerminate(
            new ConsoleTerminateEvent(
                $commandEvent->getCommand(),
                $commandEvent->getInput(),
                $commandEvent->getOutput(),
                0
            )
        );
        self::assertFalse($handler->isHandling($record));
    }

    public function testOnCommandShouldSetStdoutNotStderr(): void
    {
        $output = $this->createMock(ConsoleOutputInterface::class);

        $commandEvent = new ConsoleCommandEvent(
            $this->createMock(Command::class),
            $this->createMock(InputInterface::class),
            $output
        );
        $output->expects(self::never())
            ->method('getErrorOutput');

        $handler = new ConsoleHandler($this->consumerState);
        $handler->onCommand($commandEvent);
    }
}
