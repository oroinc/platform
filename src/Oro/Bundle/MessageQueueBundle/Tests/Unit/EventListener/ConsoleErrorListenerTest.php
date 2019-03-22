<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Monolog\Handler\BufferHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Command\ClientConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\Command\TransportConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\EventListener\ConsoleErrorListener;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleErrorHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleErrorListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Logger|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ConsoleErrorListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(Logger::class);

        $this->listener = new ConsoleErrorListener($this->logger);
    }

    /**
     * @dataProvider commandDataProvider
     *
     * @param Command
     */
    public function testOnConsoleError($command)
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $error = new \Exception('error');
        $event = new ConsoleErrorEvent($input, $output, $error, $command);

        /** @var BufferHandler|\PHPUnit\Framework\MockObject\MockObject $firstHandler */
        $firstHandler = $this->createMock(BufferHandler::class);
        $firstHandler->expects($this->never())
            ->method('flush');

        /** @var ConsoleErrorHandler|\PHPUnit\Framework\MockObject\MockObject $secondHandler */
        $secondHandler = $this->createMock(ConsoleErrorHandler::class);
        $secondHandler->expects($this->once())
            ->method('flush');
        $this->logger
            ->expects($this->once())
            ->method('getHandlers')
            ->willReturn([$firstHandler, $secondHandler]);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Consuming interrupted, reason: error');

        $this->listener->onConsoleError($event);

        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @dataProvider ignoreDataProvider
     *
     * @param Command|null $command
     */
    public function testOnConsoleErrorIgnore($command)
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $error = new \Exception('error');
        $event = new ConsoleErrorEvent($input, $output, $error, $command);

        $this->logger
            ->expects($this->never())
            ->method('getHandlers');

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->listener->onConsoleError($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'client' => [new ClientConsumeMessagesCommand()],
            'transport' => [new TransportConsumeMessagesCommand()]
        ];
    }

    /**
     * @return array
     */
    public function ignoreDataProvider()
    {
        return [
            'null' => [null],
            'command' => [new Command()]
        ];
    }
}
