<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByRangeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByRangeTopic;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByRangeMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        $this->expectNotToPerformAssertions();

        new IndexEntitiesByRangeMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(IndexerInterface::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $this->assertEquals(
            [
                IndexEntitiesByRangeTopic::getName()
            ],
            IndexEntitiesByRangeMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectMessageIfClassIsNotSetInMessage()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $message = new Message();
        $message->setBody([
            'offset' => 123,
            'limit' => 1000,
            'jobId' => 12345,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Message is not valid.');

        $producer = $this->createMock(IndexerInterface::class);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            });
        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOffsetIsNotSetInMessage()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $message = new Message();
        $message->setBody([
            'entityClass' => 'entity-name',
            'limit' => 6789,
            'jobId' => 12345,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Message is not valid.');

        $producer = $this->createMock(IndexerInterface::class);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            });

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfLimitIsNotSetInMessage()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $message = new Message();
        $message->setBody([
            'entityClass' => 'entity-name',
            'offset' => 6789,
            'jobId' => 12345,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Message is not valid.');

        $producer = $this->createMock(IndexerInterface::class);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            });

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfEntityManagerWasNotFoundForClass()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass');

        $message = new Message();
        $message->setBody([
            'entityClass' => 'entity-name',
            'offset' => 1235,
            'limit' => 6789,
            'jobId' => 12345,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Entity manager is not defined for class: "entity-name"');

        $producer = $this->createMock(IndexerInterface::class);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            });

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
