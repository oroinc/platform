<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationsMessageProcessor;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfOwnerClassIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerIds' => [1],
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOwnerIdsIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessUpdateEmailOwner()
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
            ->with(
                'oro.email.update_email_owner_association',
                ['ownerId' => 1, 'ownerClass' => 'class', 'jobId' => 12345]
            )
        ;
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with(
                'oro.email.update_email_owner_association',
                ['ownerId' => 2, 'ownerClass' => 'class', 'jobId' => 54321]
            )
        ;

        $body = [
            'ownerClass' => 'class',
            'ownerIds' => [1,2],
        ];

        $message = new NullMessage();
        $message->setBody(json_encode($body));
        $message->setMessageId('message-id');

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with('message-id', 'oro.email.update_email_owner_associations' . ':class:'.md5('1,2'))
            ->will($this->returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }))
        ;

        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->with('oro.email.update_email_owner_association'.':class:1')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            }))
        ;

        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->with('oro.email.update_email_owner_association'.':class:2')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(54321);

                $callback($jobRunner, $job);
            }))
        ;

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
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
            [Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS],
            UpdateEmailOwnerAssociationsMessageProcessor::getSubscribedTopics()
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
