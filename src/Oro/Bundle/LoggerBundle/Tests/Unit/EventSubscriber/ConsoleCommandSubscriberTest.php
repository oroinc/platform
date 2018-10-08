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

class ConsoleCommandSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsoleCommandSubscriber */
    protected $subscriber;

    /** @var Logger|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(Logger::class);

        $this->subscriber = new ConsoleCommandSubscriber($this->logger);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                ConsoleEvents::COMMAND => [['onConsoleCommand', -1]],
                ConsoleEvents::ERROR => [['onConsoleError', -1]]
            ],
            ConsoleCommandSubscriber::getSubscribedEvents()
        );
    }

    public function testOnConsoleCommandNotRun()
    {
        /** @var ConsoleCommandEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects($this->once())
            ->method('commandShouldRun')
            ->will($this->returnValue(false));

        $event->expects($this->never())
            ->method('getInput');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->subscriber->onConsoleCommand($event);
    }

    /**
     * @dataProvider consoleCommandProvider
     *
     * @param array $arguments
     * @param array $options
     */
    public function testOnConsoleCommand(array $arguments, array $options)
    {
        $input = new InputStub('test:command', $arguments, $options);

        /** @var ConsoleCommandEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConsoleCommandEvent::class);
        $event->expects($this->once())
            ->method('commandShouldRun')
            ->will($this->returnValue(true));

        $event->expects($this->once())
            ->method('getInput')
            ->will($this->returnValue($input));

        $context = [];

        if ($arguments) {
            $context['arguments'] = $arguments;
        }

        if ($options) {
            $context['options'] = $options;
        }

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(sprintf('Launched command "%s"', 'test:command'), $context);

        $this->subscriber->onConsoleCommand($event);
    }

    /**
     * @dataProvider consoleCommandProvider
     *
     * @param array $arguments
     * @param array $options
     */
    public function testOnConsoleError(array $arguments, array $options)
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

        $this->logger
            ->expects($this->once())
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

    /**
     * @return array
     */
    public function consoleCommandProvider()
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
