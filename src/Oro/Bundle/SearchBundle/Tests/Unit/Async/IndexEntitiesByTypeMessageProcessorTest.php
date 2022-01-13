<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByTypeTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByTypeMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredAttributes()
    {
        $this->expectNotToPerformAssertions();

        new IndexEntitiesByTypeMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(JobRunner::class),
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $this->assertEquals(
            [
                IndexEntitiesByTypeTopic::getName()
            ],
            IndexEntitiesByTypeMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectMessageIfEntityManagerWasNotFoundForClass()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Entity manager is not defined for class: "entity-name"');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            });

        $message = new Message();
        $message->setBody([
            'entityClass' => 'entity-name',
            'jobId' => 12345,
        ]);

        $processor = new IndexEntitiesByTypeMessageProcessor(
            $doctrine,
            $jobRunner,
            self::getMessageProducer(),
            $logger
        );
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
