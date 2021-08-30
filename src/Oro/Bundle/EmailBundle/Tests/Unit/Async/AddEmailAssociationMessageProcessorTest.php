<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AddEmailAssociationMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AddEmailAssociationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AddEmailAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfEmailIdIsMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([
            'targetClass' => 'class',
            'targetId' => 123,
        ], JSON_THROW_ON_ERROR));

        $processor = new AddEmailAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetClassMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([
            'emailId' => 1,
            'targetId' => 123,
        ], JSON_THROW_ON_ERROR));

        $processor = new AddEmailAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetIdMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([
            'emailId' => 1,
            'targetClass' => 'class',
        ], JSON_THROW_ON_ERROR));

        $processor = new AddEmailAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAddAssociation()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');

        $manager = $this->createMock(AssociationManager::class);
        $manager->expects($this->once())
            ->method('processAddAssociation')
            ->with([456], 'class', 123);

        $message = new Message();
        $body = [
            'jobId' => 123,
            'emailId' => 456,
            'targetClass' => 'class',
            'targetId' => 123,
        ];
        $message->setBody(json_encode($body, JSON_THROW_ON_ERROR));

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(123)
            ->willReturnCallback(function ($name, $callback) use ($body) {
                $callback($body);

                return true;
            });

        $processor = new AddEmailAssociationMessageProcessor(
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
            [Topics::ADD_ASSOCIATION_TO_EMAIL],
            AddEmailAssociationMessageProcessor::getSubscribedTopics()
        );
    }
}
