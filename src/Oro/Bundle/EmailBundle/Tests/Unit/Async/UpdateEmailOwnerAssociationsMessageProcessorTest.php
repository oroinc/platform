<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationsMessageProcessor;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldProcessUpdateEmailOwner()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');
        $logger->expects($this->once())
            ->method('info')
            ->with('Sent "2" messages');

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    'oro.email.update_email_owner_association',
                    ['ownerId' => 1, 'ownerClass' => 'class', 'jobId' => 12345]
                ],
                [
                    'oro.email.update_email_owner_association',
                    ['ownerId' => 2, 'ownerClass' => 'class', 'jobId' => 54321]
                ]
            );

        $body = [
            'ownerClass' => 'class',
            'ownerIds' => [1,2],
        ];

        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('message-id');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            });

        $jobRunner->expects($this->exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                ['oro.email.update_email_owner_association'.':class:1'],
                ['oro.email.update_email_owner_association'.':class:2']
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($name, $callback) use ($jobRunner) {
                    $job = new Job();
                    $job->setId(12345);

                    $callback($jobRunner, $job);
                }),
                new ReturnCallback(function ($name, $callback) use ($jobRunner) {
                    $job = new Job();
                    $job->setId(54321);

                    $callback($jobRunner, $job);
                })
            );

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $producer,
            $jobRunner,
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [UpdateEmailOwnerAssociationsTopic::getName()],
            UpdateEmailOwnerAssociationsMessageProcessor::getSubscribedTopics()
        );
    }
}
