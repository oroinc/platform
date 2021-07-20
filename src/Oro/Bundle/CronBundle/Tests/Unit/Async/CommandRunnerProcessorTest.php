<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerProcessor;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CommandRunnerProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var CommandRunnerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $commandRunner;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CommandRunnerProcessor */
    private $commandRunnerProcessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->commandRunner = $this->createMock(CommandRunnerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->commandRunnerProcessor = new CommandRunnerProcessor($this->jobRunner, $this->commandRunner);
        $this->commandRunnerProcessor->setLogger($this->logger);
    }

    public function testProcessWithoutCommand()
    {
        $message = new Message();
        $message->setBody('');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message: empty command');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @dataProvider argumentsDataProvider
     */
    public function testProcessInvalidArguments($arguments)
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'command' => 'test:command',
            'arguments' => $arguments
        ]));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message: "arguments" must be of type array', ['message' => $message]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
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

        $message = new Message();
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

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param bool $jobResult
     * @param string $expectedResult
     */
    public function testProcessDelayedJob($jobResult, $expectedResult)
    {
        $jobId = 'job_id';
        $commandName = 'test:command';
        $commandArguments = ['argKey' => 'argVal'];

        $message = new Message();
        $message->setBody(JSON::encode([
            'jobId' => $jobId,
            'command' => $commandName,
            'arguments' => $commandArguments,
        ]));

        $this->logger->expects($this->never())
            ->method('critical');

        $this->jobRunner->expects($this->never())
            ->method('runUnique');
        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(
                $jobId,
                function () use ($commandName, $commandArguments) {
                }
            )
            ->willReturn($jobResult);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
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
