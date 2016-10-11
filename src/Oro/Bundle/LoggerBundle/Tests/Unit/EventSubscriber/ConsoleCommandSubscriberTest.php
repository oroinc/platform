<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventSubscriber;

use Monolog\Logger;

use Oro\Bundle\LoggerBundle\EventSubscriber\ConsoleCommandSubscriber;
use Oro\Bundle\LoggerBundle\Tests\Unit\Stub\InputStub;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

class ConsoleCommandSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsoleCommandSubscriber */
    protected $subscriber;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->getMock(Logger::class, [], [], '', false);

        $this->subscriber = new ConsoleCommandSubscriber($this->logger);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                ConsoleEvents::COMMAND => [['onConsoleCommand', -1]],
                ConsoleEvents::EXCEPTION => [['onConsoleException', -1]]
            ],
            ConsoleCommandSubscriber::getSubscribedEvents()
        );
    }

    public function testOnConsoleCommandNotRun()
    {
        /** @var ConsoleCommandEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(ConsoleCommandEvent::class, [], [], '', false);
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
     * @param string $command
     * @param array $arguments
     * @param array $options
     */
    public function testOnConsoleCommand($command, array $arguments, array $options)
    {
        $input = new InputStub($command, $arguments, $options);

        /** @var ConsoleCommandEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(ConsoleCommandEvent::class, [], [], '', false);
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
            ->with(sprintf('Launched command "%s"', $command), $context);

        $this->subscriber->onConsoleCommand($event);
    }

    /**
     * @dataProvider consoleCommandProvider
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     */
    public function testOnConsoleException($command, array $arguments, array $options)
    {
        $input = new InputStub($command, $arguments, $options);

        /** @var ConsoleExceptionEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(ConsoleExceptionEvent::class, [], [], '', false);

        $event->expects($this->once())
            ->method('getInput')
            ->will($this->returnValue($input));

        $event->expects($this->once())
            ->method('getExitCode')
            ->will($this->returnValue(0));

        $exception = new \Exception('exception message');
        $event->expects($this->once())
            ->method('getException')
            ->will($this->returnValue($exception));

        $context = ['exit_code' => 0];

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
                    $command,
                    'exception message'
                ),
                $context
            );

        $this->subscriber->onConsoleException($event);
    }

    /**
     * @return array
     */
    public function consoleCommandProvider()
    {
        return [
            'without arguments and options' => [
                'command' => 'test:command',
                'arguments' => [
                    'command' => 'test:command'
                ],
                'options' => []
            ],
            'with arguments' => [
                'command' => 'test:command argumentValue',
                'arguments' => [
                    'command' => 'test:command',
                    'argumentKey' => 'argumentValue'
                ],
                'options' => []
            ],
            'with options' => [
                'command' => 'test:command --optionKey=optionValue',
                'arguments' => [
                    'command' => 'test:command'
                ],
                'options' => [
                    'optionKey' => 'optionValue'
                ]
            ],
            'with arguments and options' => [
                'command' => 'test:command argumentValue --optionKey=optionValue',
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
