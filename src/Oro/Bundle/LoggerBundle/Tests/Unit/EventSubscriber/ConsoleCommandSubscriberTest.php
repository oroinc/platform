<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventSubscriber;

use Monolog\Logger;
use Oro\Bundle\LoggerBundle\EventSubscriber\ConsoleCommandSubscriber;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private ConsoleCommandSubscriber $subscriber;

    private Logger|\PHPUnit\Framework\MockObject\MockObject $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);

        $this->subscriber = new ConsoleCommandSubscriber($this->logger);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                ConsoleEvents::COMMAND => [['onConsoleCommand', -1]],
                ConsoleEvents::ERROR => [['onConsoleError', -1]]
            ],
            ConsoleCommandSubscriber::getSubscribedEvents()
        );
    }

    public function testOnConsoleCommandNotRun(): void
    {
        $event = new ConsoleCommandEvent(
            null,
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $event->disableCommand();

        $this->logger->expects(self::never())
            ->method('info');

        $this->subscriber->onConsoleCommand($event);
    }

    /**
     * @dataProvider consoleCommandProvider
     */
    public function testOnConsoleCommand(array $arguments, array $options): void
    {
        $input = new InputStub('test:command', $arguments, $options);

        $event = new ConsoleCommandEvent(
            new Command('test:command'),
            $input,
            $this->createMock(OutputInterface::class)
        );

        $context = [];

        if ($arguments) {
            $context['arguments'] = $arguments;
        }

        if ($options) {
            $context['options'] = $options;
        }

        $this->logger->expects(self::once())
            ->method('info')
            ->with(sprintf('Launched command "%s"', 'test:command'), $context);

        $this->subscriber->onConsoleCommand($event);
    }

    /**
     * @dataProvider consoleCommandProvider
     */
    public function testOnConsoleError(array $arguments, array $options): void
    {
        $input = new InputStub('test:command', $arguments, $options);

        $exception = new \Exception('exception message', 3);

        $event  = new ConsoleErrorEvent($input, new OutputStub(), $exception, new Command('test:command'));

        $context = ['exit_code' => 3, 'exception' => $exception];

        if ($arguments) {
            $context['arguments'] = $arguments;
        }

        if ($options) {
            $context['options'] = $options;
        }

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'An error occurred while running command "%s". %s',
                    'test:command',
                    'exception message'
                ),
                $context
            );

        $this->subscriber->onConsoleError($event);
    }

    public function consoleCommandProvider(): array
    {
        return [
            'without arguments and options' => [
                'arguments' => [
                    'command' => 'test:command'
                ],
                'options' => [],
            ],
            'with arguments' => [
                'arguments' => [
                    'command' => 'test:command',
                    'argumentKey' => 'argumentValue'
                ],
                'options' => []
            ],
            'with options' => [
                'arguments' => [
                    'command' => 'test:command'
                ],
                'options' => [
                    'optionKey' => 'optionValue'
                ]
            ],
            'with arguments and options' => [
                'arguments' => [
                    'command' => 'test:command',
                    'argumentKey' => 'argumentValue'
                ],
                'options' => [
                    'optionKey' => 'optionValue'
                ]
            ]
        ];
    }
}
