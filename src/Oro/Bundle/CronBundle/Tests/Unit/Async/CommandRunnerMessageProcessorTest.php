<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerMessageProcessor;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CommandRunnerMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeConstructedWithAllRequiredArguments()
    {
        new  CommandRunnerMessageProcessor(
            $this->createCommandRunnerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }


    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::RUN_COMMAND
        ];

        $this->assertEquals($expectedSubscribedTopics, CommandRunnerMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectIdMessageHasNoCommand()
    {
        $commandRunner = $this->createCommandRunnerMock();
        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([]));

        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                'Got invalid message: empty command',
                [
                    'message' => $message
                ]
            )
        ;

        $processor = new CommandRunnerMessageProcessor($commandRunner, $this->createJobRunnerMock(), $logger);
        $result = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunCommandWithEmptyArgumentsAndLogOutput()
    {
        $testCommandName = 'oro:cron:test';
        $testCommandOutput = 'Command OK';

        $commandRunner = $this->createCommandRunnerMock();

        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([
            'command' => $testCommandName
        ]));

        $commandRunner
            ->expects($this->once())
            ->method('run')
            ->with($testCommandName, [])
            ->willReturn($testCommandOutput)
        ;

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Ran command '.$testCommandName.' with arguments: . Got output '.$testCommandOutput,
                [
                    'command' => $testCommandName,
                    'arguments' => [],
                    'output' => $testCommandOutput
                ]
            )
        ;

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->will($this->returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }))
        ;

        $processor = new CommandRunnerMessageProcessor($commandRunner, $jobRunner, $logger);
        $result = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRunCommandWithArgumentsAndLogOutput()
    {
        $testCommandName = 'oro:cron:test';
        $testCommandOutput = 'Command OK';
        $testArguments = [
            '--a1' => 'v1',
            'a2'
        ];

        $commandRunner = $this->createCommandRunnerMock();

        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([
            'command' => $testCommandName,
            'arguments' => $testArguments
        ]));

        $commandRunner
            ->expects($this->once())
            ->method('run')
            ->with($testCommandName, $testArguments)
            ->willReturn($testCommandOutput)
        ;

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Ran command '.$testCommandName.' with arguments: '.implode(' ', $testArguments).
                '. Got output '.$testCommandOutput,
                [
                    'command' => $testCommandName,
                    'arguments' => $testArguments,
                    'output' => $testCommandOutput
                ]
            )
        ;

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->will($this->returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }))
        ;

        $processor = new CommandRunnerMessageProcessor($commandRunner, $jobRunner, $logger);
        $result = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | CommandRunnerInterface
     */
    private function createCommandRunnerMock()
    {
        return $this->createMock(CommandRunnerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
