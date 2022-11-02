<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationTopic;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationMessageProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpdateEmailOwnerAssociationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createMock(AssociationManager::class),
            $this->createMock(JobRunner::class)
        );
    }

    public function testShouldProcessUpdateEmailOwner()
    {
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
        $message->setBody($data);

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
            $jobRunner
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [UpdateEmailOwnerAssociationTopic::getName()],
            UpdateEmailOwnerAssociationMessageProcessor::getSubscribedTopics()
        );
    }
}
