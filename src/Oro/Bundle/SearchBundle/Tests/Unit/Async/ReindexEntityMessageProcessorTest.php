<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\ReindexEntityMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByTypeTopic;
use Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ReindexEntityMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new ReindexEntityMessageProcessor(
            $this->createMock(IndexerInterface::class),
            $this->createMock(JobRunner::class),
            $this->createMock(MessageProducerInterface::class)
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [ReindexTopic::getName()];

        $this->assertEquals($expectedSubscribedTopics, ReindexEntityMessageProcessor::getSubscribedTopics());
    }

    public function testShouldReindexWholeIndexIfMessageIsEmpty()
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($this->once())
            ->method('resetIndex');
        $indexer->expects($this->once())
            ->method('getClassesForReindex')
            ->willReturn(['class-name']);

        $message = new Message();
        $message->setMessageId('message-id');
        $message->setBody('');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            });
        $jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with('oro.search.index_entity_type:class-name')
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            });

        $processor = new ReindexEntityMessageProcessor($indexer, $jobRunner, self::getMessageProducer());
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            IndexEntitiesByTypeTopic::getName(),
            ['entityClass' => 'class-name', 'jobId' => 12345]
        );
    }

    public function testShouldReindexOnlySingleClass()
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($this->once())
            ->method('resetIndex')
            ->with('class-name');
        $indexer->expects($this->once())
            ->method('getClassesForReindex')
            ->with('class-name')
            ->willReturn(['class-name']);

        $message = new Message();
        $message->setMessageId('message-id');
        $message->setBody('class-name');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            });
        $jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with('oro.search.index_entity_type:class-name')
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            });

        $processor = new ReindexEntityMessageProcessor($indexer, $jobRunner, self::getMessageProducer());
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            IndexEntitiesByTypeTopic::getName(),
            ['entityClass' => 'class-name', 'jobId' => 12345]
        );
    }

    public function testShouldReindexArrayOfClasses()
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($this->once())
            ->method('resetIndex')
            ->with('class-name');
        $indexer->expects($this->once())
            ->method('getClassesForReindex')
            ->with('class-name')
            ->willReturn(['class-name']);

        $message = new Message();
        $message->setMessageId('message-id');
        $message->setBody(['class-name']);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            });
        $jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with('oro.search.index_entity_type:class-name')
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            });

        $processor = new ReindexEntityMessageProcessor($indexer, $jobRunner, self::getMessageProducer());
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            IndexEntitiesByTypeTopic::getName(),
            ['entityClass' => 'class-name', 'jobId' => 12345]
        );
    }
}
