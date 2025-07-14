<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SyncBundle\EventListener\WebsocketServerCommandListener;
use Oro\Bundle\SyncBundle\WebSocket\DsnBasedParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class WebsocketServerCommandListenerTest extends TestCase
{
    private const COMMAND_NAME = 'gos:websocket:server';

    private DsnBasedParameters&MockObject $dsnParameters;
    private WebsocketServerCommandListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->dsnParameters = $this->createMock(DsnBasedParameters::class);

        $this->listener = new WebsocketServerCommandListener($this->dsnParameters, self::COMMAND_NAME);
    }

    public function testOnConsoleCommandWhenNotSupported(): void
    {
        $command = new Command('oro:sample:command');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);

        $this->dsnParameters->expects(self::never())
            ->method(self::anything());

        self::assertTrue($event->commandShouldRun());

        $this->listener->onConsoleCommand($event);

        self::assertTrue($event->commandShouldRun());
        self::assertEquals('', $output->fetch());
    }

    public function testOnConsoleCommandWhenHostIsConfigured(): void
    {
        $command = new Command(self::COMMAND_NAME);
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);

        $this->dsnParameters->expects(self::once())
            ->method('getHost')
            ->willReturn('example.org');

        self::assertTrue($event->commandShouldRun());

        $this->listener->onConsoleCommand($event);

        self::assertTrue($event->commandShouldRun());
        self::assertEquals('', $output->fetch());
    }

    public function testOnConsoleCommandWhenHostIsNotConfigured(): void
    {
        $command = new Command(self::COMMAND_NAME);
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);

        $this->dsnParameters->expects(self::once())
            ->method('getHost')
            ->willReturn('');

        self::assertTrue($event->commandShouldRun());

        $this->listener->onConsoleCommand($event);

        self::assertFalse($event->commandShouldRun());
        self::assertStringContainsString('Websocket server is not configured.', $output->fetch());
    }
}
