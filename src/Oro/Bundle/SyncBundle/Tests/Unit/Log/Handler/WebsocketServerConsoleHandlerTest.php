<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Log\Handler;

use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Oro\Bundle\SyncBundle\Log\Handler\WebsocketServerConsoleHandler;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler as SymfonyConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketServerConsoleHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SymfonyConsoleHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $consoleHandler;

    /** @var WebsocketServerConsoleHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->consoleHandler = $this->createMock(SymfonyConsoleHandler::class);

        $this->handler = new WebsocketServerConsoleHandler($this->consoleHandler);
    }

    public function testOnCommandDoesNothingWhenNoCommand(): void
    {
        $event = new ConsoleCommandEvent(
            null,
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->consoleHandler->expects(self::never())
            ->method(self::anything());

        $this->handler->onCommand($event);
    }

    public function testOnCommandDoesNothingWhenCommandNotWebsocketServer(): void
    {
        $event = new ConsoleCommandEvent(
            new Command('sample-command'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->consoleHandler->expects(self::never())
            ->method(self::anything());

        $this->handler->onCommand($event);
    }

    public function testOnCommandSetsOutputWhenCommandWebsocketServer(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $event = new ConsoleCommandEvent(
            new Command(WebsocketServerCommand::getDefaultName()),
            $this->createMock(InputInterface::class),
            $output
        );

        $this->consoleHandler->expects(self::once())
            ->method('setOutput')
            ->with($output);

        $this->handler->onCommand($event);

        // Ensures that nothing happens when nested level is higher than 1.
        $this->handler->onCommand($event);
    }

    public function testOnTerminateDoesNothingWhenCommandNotWebsocketServer(): void
    {
        $event = new ConsoleTerminateEvent(
            new Command('sample-command'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            Command::SUCCESS
        );

        $this->consoleHandler->expects(self::never())
            ->method(self::anything());

        $this->handler->onTerminate($event);
    }

    public function testOnTerminateWhenCommandWebsocketServer(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $event = new ConsoleCommandEvent(
            new Command(WebsocketServerCommand::getDefaultName()),
            $this->createMock(InputInterface::class),
            $output
        );

        $this->consoleHandler->expects(self::once())
            ->method('setOutput')
            ->with($output);

        $terminateEvent = new ConsoleTerminateEvent(
            new Command(WebsocketServerCommand::getDefaultName()),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            Command::SUCCESS
        );

        $this->consoleHandler->expects(self::once())
            ->method('onTerminate')
            ->with($terminateEvent);

        $this->handler->onCommand($event);
        $this->handler->onTerminate($terminateEvent);

        // Ensures that nothing happens when nested level is not 1.
        $this->handler->onTerminate($terminateEvent);
    }
}
