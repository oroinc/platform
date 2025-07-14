<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventSubscriber;

use Oro\Bundle\LoggerBundle\EventSubscriber\ConsoleErrorSubscriber;
use Oro\Bundle\MessageQueueBundle\Command\CleanupCommand;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;

class ConsoleErrorSubscriberTest extends TestCase
{
    private ConsoleErrorSubscriber $subscriber;
    private LoggerInterface&MockObject $logger;
    private ErrorHandler $handler;
    private InputInterface&MockObject $input;
    private OutputInterface&MockObject $output;

    private static array $defaultLoggers = [
        E_DEPRECATED => [null, LogLevel::INFO],
        E_USER_DEPRECATED => [null, LogLevel::INFO],
        E_NOTICE => [null, LogLevel::WARNING],
        E_USER_NOTICE => [null, LogLevel::WARNING],
        E_WARNING => [null, LogLevel::WARNING],
        E_USER_WARNING => [null, LogLevel::WARNING],
        E_COMPILE_WARNING => [null, LogLevel::WARNING],
        E_CORE_WARNING => [null, LogLevel::WARNING],
    ];

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new ConsoleErrorSubscriber($this->logger);

        $this->handler = ErrorHandler::register();
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * @dataProvider commandDataProvider
     */
    public function testConfigure(string $command): void
    {
        $this->subscriber->configure(
            new ConsoleCommandEvent($this->createMock($command), $this->input, $this->output)
        );

        $this->assertEquals(
            self::$defaultLoggers + [
                E_USER_ERROR => [$this->logger, LogLevel::CRITICAL],
                E_RECOVERABLE_ERROR => [$this->logger, LogLevel::CRITICAL],
                E_COMPILE_ERROR => [$this->logger, LogLevel::CRITICAL],
                E_PARSE => [$this->logger, LogLevel::CRITICAL],
                E_ERROR => [$this->logger, LogLevel::CRITICAL],
                E_CORE_ERROR => [$this->logger, LogLevel::CRITICAL],
            ],
            $this->handler->setLoggers([])
        );
    }

    public function commandDataProvider(): array
    {
        return [
            [ConsumeMessagesCommand::class],
            [CleanupCommand::class]
        ];
    }
}
