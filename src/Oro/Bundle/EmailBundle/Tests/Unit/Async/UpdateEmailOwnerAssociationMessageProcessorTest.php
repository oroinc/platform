<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationMessageProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfOwnerClassIsMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([
            'ownerId' => [1],
        ], JSON_THROW_ON_ERROR));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOwnerIdIsMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
        ], JSON_THROW_ON_ERROR));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessUpdateEmailOwner()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');

        $manager = $this->createMock(AssociationManager::class);
        $manager->expects($this->once())
            ->method('processUpdateEmailOwner')
            ->with('class', [1]);

        $data = [
            'ownerClass' => 'class',
            'ownerId' => 1,
            'jobId' => 12345
        ];

        $message = new Message();
        $message->setBody(json_encode($data, JSON_THROW_ON_ERROR));

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($data) {
                $callback($data);

                return true;
            });

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $manager,
            $jobRunner,
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::UPDATE_EMAIL_OWNER_ASSOCIATION],
            UpdateEmailOwnerAssociationMessageProcessor::getSubscribedTopics()
        );
    }
}
