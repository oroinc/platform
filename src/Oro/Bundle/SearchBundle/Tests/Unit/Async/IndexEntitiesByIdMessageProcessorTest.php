<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByIdMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private IndexEntitiesByIdMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = $this->createMock(AbstractIndexer::class);

        $this->processor = new IndexEntitiesByIdMessageProcessor(
            $this->jobRunner,
            $doctrineHelper,
            $indexer
        );
        $this->processor->setLogger($this->logger);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [IndexEntitiesByIdTopic::getName()];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByIdMessageProcessor::getSubscribedTopics());
    }

    public function testBuildJobNameForMessage()
    {
        $message = new Message();
        $message->setMessageId('message id');
        $message->setBody(['class' => 'class-name', 'entityIds' => ['id']]);

        $this->logger->expects($this->never())
            ->method('error');

        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturn(true)
            ->with($message);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
