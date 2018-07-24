<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AddEmailAssociationsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AddEmailAssociationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AddEmailAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfEmailIdsIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'targetClass' => 'class',
            'targetId' => 123,
        ]));

        $processor = new AddEmailAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetClassMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetId' => 123,
        ]));

        $processor = new AddEmailAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetIdMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetClass' => 'class',
        ]));

        $processor = new AddEmailAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAddAssociation()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Sent "2" messages'))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->at(0))
            ->method('send')
            ->with('oro.email.add_association_to_email', [
                'emailId' => 1,
                'targetClass' => 'class',
                'targetId' => 123,
                'jobId' => 12345
            ])
        ;
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with('oro.email.add_association_to_email', [
                'emailId' => 2,
                'targetClass' => 'class',
                'targetId' => 123,
                'jobId' => 54321
            ])
        ;

        $body = [
            'emailIds' => [1,2],
            'targetClass' => 'class',
            'targetId' => 123
        ];

        $message = new NullMessage();
        $message->setBody(json_encode($body));
        $message->setMessageId('message-id');

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with('message-id', 'oro.email.add_association_to_emails' . ':class:123:'.md5('1,2'))
            ->will($this->returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }))
        ;

        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->with('oro.email.add_association_to_email'.':class:123:1')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            }))
        ;

        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->with('oro.email.add_association_to_email'.':class:123:2')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(54321);

                $callback($jobRunner, $job);
            }))
        ;

        $processor = new AddEmailAssociationsMessageProcessor(
            $producer,
            $jobRunner,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::ADD_ASSOCIATION_TO_EMAILS],
            AddEmailAssociationsMessageProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)->disableOriginalConstructor()->getMock();
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
