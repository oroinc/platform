<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\CronBundle\Async\CommandRunnerMessageProcessor;
use Oro\Bundle\CronBundle\Async\Topics;

class CommandRunnerMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeConstructedWithAllRequiredArguments()
    {
        new  CommandRunnerMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createLoggerMock(),
            $this->createProducerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::RUN_COMMAND
        ];

        self::assertEquals($expectedSubscribedTopics, CommandRunnerMessageProcessor::getSubscribedTopics());
    }

    public function testReturnRejectIfMessageHasNoCommand()
    {
        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([]));

        $logger
            ->expects(self::once())
            ->method('critical')
            ->with(
                'Got invalid message: empty command',
                [
                    'message' => $message
                ]
            )
        ;

        $processor = new CommandRunnerMessageProcessor(
            $this->createJobRunnerMock(),
            $logger,
            $this->createProducerMock()
        );
        $result = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testReturnRejectIfInvalidCommandArgsFormat()
    {
        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();

        $message = new NullMessage();
        $message->setBody(json_encode(['command' => 'foo', 'arguments' => 0]));

        $logger
            ->expects(self::once())
            ->method('critical')
            ->with(
                'Got invalid message: "arguments" must be of type array',
                [
                    'message' => $message
                ]
            );

        $processor = new CommandRunnerMessageProcessor(
            $this->createJobRunnerMock(),
            $logger,
            $this->createProducerMock()
        );
        $result = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendDelayedJobMessage()
    {
        $testCommandName = 'oro:cron:test';
        $testArguments = ['--a1' => 'v1', 'a2'];
        $messageId = 'x.123';
        $message = new NullMessage();
        $body    = [
            'command'   => $testCommandName,
            'arguments' => $testArguments
        ];
        $message->setBody(json_encode($body));
        $message->setMessageId($messageId);
        $jobId = 100;

        $producer = $this->createProducerMock();
        $session = $this->createSessionMock();
        $logger = $this->createLoggerMock();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(Topics::RUN_COMMAND_DELAYED, array_merge($body, ['jobId' => $jobId]));
        $jobName = sprintf('oro:cron:run_command:%s-%s', $testCommandName, implode('-', $testArguments));
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->with($messageId, $jobName)
            ->will(self::returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }));
        $jobRunner
            ->expects(self::once())
            ->method('createDelayed')
            ->with($jobName . '.delayed')
            ->will(self::returnCallback(function ($name, $callback) use ($jobRunner, $jobId) {
                $job = new Job();
                $job->setId($jobId);

                $callback($jobRunner, $job);
            }));

        $processor = new CommandRunnerMessageProcessor(
            $jobRunner,
            $logger,
            $producer
        );
        $result = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $result);
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | MessageProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
