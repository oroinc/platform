<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerMessageProcessor;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CommandRunnerMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var CommandRunnerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $commandRunner;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CommandRunnerMessageProcessor */
    private $commandRunnerProcessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->commandRunner = $this->createMock(CommandRunnerInterface::class);
        $producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->commandRunnerProcessor = new CommandRunnerMessageProcessor($this->jobRunner, $this->logger, $producer);
        $this->commandRunnerProcessor->setCommandRunner($this->commandRunner);
    }

    public function testProcessWithoutCommand()
    {
        $session = new NullSession();
        $message = new NullMessage();
        $message->setBody('');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message: empty command');

        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @dataProvider argumentsDataProvider
     *
     * @param $arguments
     */
    public function testProcessInvalidArguments($arguments)
    {
        $session = new NullSession();
        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'command' => 'test:command',
            'arguments' => $arguments
        ]));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message: "arguments" must be of type array', ['message' => $message]);

        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param bool $jobResult
     * @param string $expectedResult
     */
    public function testProcessUniqueJob($jobResult, $expectedResult)
    {
        $commandName = 'test:command';
        $commandArguments = ['argKey' => 'argVal'];

        $session = new NullSession();
        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'command' => $commandName,
            'arguments' => $commandArguments,
        ]));

        $this->logger->expects($this->never())
            ->method('critical');

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with(
                null,
                'oro:cron:run_command:test:command-argKey=argVal',
                function () use ($commandName, $commandArguments) {
                }
            )
            ->willReturn($jobResult);
        $this->jobRunner->expects($this->never())
            ->method('runDelayed');

        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function argumentsDataProvider()
    {
        return [
            'bool' => [true],
            'int' => [1],
            'float' => [1.01],
            'string' => ['John Doe'],
        ];
    }

    /**
     * @return array
     */
    public function jobDataProvider()
    {
        return [
            'ACK' => [
                'jobResult' => true,
                'expectedResult' => MessageProcessorInterface::ACK
            ],
            'REJECT' => [
                'jobResult' => false,
                'expectedResult' => MessageProcessorInterface::REJECT
            ]
        ];
    }
}
