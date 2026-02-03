<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\Log\Handler;

use Oro\Bundle\SyncBundle\Log\Handler\WebsocketServerConsoleHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler as SymfonyConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketServerConsoleHandlerTest extends TestCase
{
    private SymfonyConsoleHandler $consoleHandler;
    private WebsocketServerConsoleHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->consoleHandler = new SymfonyConsoleHandler();
        $this->handler = new WebsocketServerConsoleHandler($this->consoleHandler);
    }

    public function testOnCommandDoesNothingWhenNoCommand(): void
    {
        $event = $this->createConsoleCommandEvent();
        $this->handler->onCommand($event);

        self::assertNull($this->getConsoleHandlerOutput());
    }

    public function testOnCommandDoesNothingWhenCommandNotWebsocketServer(): void
    {
        $event = $this->createConsoleCommandEvent('sample-command');
        $this->handler->onCommand($event);

        self::assertNull($this->getConsoleHandlerOutput());
    }

    public function testOnCommandSetsOutputWhenCommandWebsocketServer(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $event = $this->createConsoleCommandEvent('gos:websocket:server', $output);

        $this->handler->onCommand($event);
        self::assertSame($output, $this->getConsoleHandlerOutput());

        // Ensures that nothing happens when nested level is higher than 1.
        $this->handler->onCommand(clone $event);
        self::assertSame($output, $this->getConsoleHandlerOutput());
    }

    public function testOnTerminateDoesNothingWhenCommandNotWebsocketServer(): void
    {
        $event = $this->createConsoleCommandEvent();
        $this->handler->onCommand($event);

        $event = $this->createConsoleTerminateEvent('sample-command');
        $this->handler->onTerminate($event);

        self::assertNull($this->getConsoleHandlerOutput());
    }

    public function testOnTerminateWhenCommandWebsocketServer(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $event = $this->createConsoleCommandEvent('gos:websocket:server', $output);
        $terminateEvent = $this->createConsoleTerminateEvent('gos:websocket:server');

        $this->handler->onCommand($event);
        $this->handler->onTerminate($terminateEvent);

        self::assertNull($this->getConsoleHandlerOutput());

        // Ensures that nothing happens when nested level is not 1.
        $this->handler->onTerminate($terminateEvent);
        self::assertNull($this->getConsoleHandlerOutput());
    }

    private function createConsoleCommandEvent(
        ?string $commandName = null,
        ?OutputInterface $output = null
    ): ConsoleCommandEvent {
        return new ConsoleCommandEvent(
            new Command($commandName),
            $this->createMock(InputInterface::class),
            $output ?: $this->createMock(OutputInterface::class)
        );
    }

    private function createConsoleTerminateEvent(?string $commandName = null): ConsoleTerminateEvent
    {
        return new ConsoleTerminateEvent(
            new Command($commandName),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            Command::SUCCESS
        );
    }

    /**
     * @throws \ReflectionException
     */
    private function getConsoleHandlerOutput(): ?OutputInterface
    {
        $property = new \ReflectionProperty($this->consoleHandler, 'output');

        return $property->getValue($this->consoleHandler);
    }
}
