<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AddEmailAssociationMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;

class AddEmailAssociationMessageProcessorTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        $this->expectNotToPerformAssertions();

        new AddEmailAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class)
        );
    }

    public function testShouldProcessAddAssociation(): void
    {
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
        $message->setBody($body);

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
            $jobRunner
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics(): void
    {
        $this->assertEquals(
            [AddEmailAssociationTopic::getName()],
            AddEmailAssociationMessageProcessor::getSubscribedTopics()
        );
    }
}
