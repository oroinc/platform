<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerProcessor;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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

    /**
     * @dataProvider jobDataProvider
     */
    public function testProcessUniqueJob(bool $jobResult, string $expectedResult)
    {
        $commandName = 'test:command';
        $commandArguments = ['argKey' => 'argVal'];

        $message = new Message();
        $message->setBody([
            'command' => $commandName,
            'arguments' => $commandArguments,
        ]);

        $this->logger->expects($this->never())
            ->method('critical');

        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturn($jobResult);
        $this->jobRunner->expects($this->never())
            ->method('runDelayed');

        $session = $this->createMock(SessionInterface::class);
        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider jobDataProvider
     */
    public function testProcessDelayedJob(bool $jobResult, string $expectedResult)
    {
        $jobId = 'job_id';
        $commandName = 'test:command';
        $commandArguments = ['argKey' => 'argVal'];

        $message = new Message();
        $message->setBody([
            'jobId' => $jobId,
            'command' => $commandName,
            'arguments' => $commandArguments,
        ]);

        $this->logger->expects($this->never())
            ->method('critical');

        $this->jobRunner->expects($this->never())
            ->method('runUnique');
        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturn($jobResult);

        $session = $this->createMock(SessionInterface::class);
        $result = $this->commandRunnerProcessor->process($message, $session);
        $this->assertEquals($expectedResult, $result);
    }

    public function argumentsDataProvider(): array
    {
        return [
            'bool' => [true],
            'int' => [1],
            'float' => [1.01],
            'string' => ['John Doe'],
        ];
    }

    public function jobDataProvider(): array
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
