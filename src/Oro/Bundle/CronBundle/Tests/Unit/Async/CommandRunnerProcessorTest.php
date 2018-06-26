<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerProcessor;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CommandRunnerProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSubscribedTopics()
    {
        self::assertEquals([Topics::RUN_COMMAND_DELAYED], CommandRunnerProcessor::getSubscribedTopics());
    }

    public function testShouldRunCommandWithEmptyArgumentsAndLogOutput()
    {
        $testCommandName = 'oro:cron:test';
        $testCommandOutput = 'Command OK';
        $message = new NullMessage();
        $message->setBody(json_encode(['command' => $testCommandName, 'jobId' => 1]));

        $commandRunner = $this->createCommandRunnerMock();
        $logger = $this->createLoggerMock();

        $commandRunner
            ->expects(self::once())
            ->method('run')
            ->with($testCommandName, [])
            ->willReturn($testCommandOutput);

        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Ran command '.$testCommandName.'. Got output '.$testCommandOutput,
                [
                    'command' => $testCommandName,
                    'arguments' => [],
                    'output' => $testCommandOutput
                ]
            );

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->will(self::returnCallback(function ($ownerId, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }));

        $processor = new CommandRunnerProcessor($commandRunner, $jobRunner, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRunCommandWithArgumentsAndLogOutput()
    {
        $testCommandName = 'oro:cron:test';
        $testCommandOutput = 'Command OK';
        $testArguments = ['--a1' => 'v1', 'a2'];
        $message = new NullMessage();
        $message->setBody(
            json_encode(['command' => $testCommandName, 'arguments' => $testArguments, 'jobId' => 1])
        );

        $commandRunner = $this->createCommandRunnerMock();
        $logger = $this->createLoggerMock();

        $commandRunner
            ->expects(self::once())
            ->method('run')
            ->with($testCommandName, $testArguments)
            ->willReturn($testCommandOutput);

        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Ran command '.$testCommandName.'. Got output '.$testCommandOutput,
                [
                    'command' => $testCommandName,
                    'arguments' => $testArguments,
                    'output' => $testCommandOutput
                ]
            );

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->will(self::returnCallback(function ($ownerId, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }));

        $processor = new CommandRunnerProcessor($commandRunner, $jobRunner, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | CommandRunnerInterface
     */
    private function createCommandRunnerMock()
    {
        return $this->createMock(CommandRunnerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
